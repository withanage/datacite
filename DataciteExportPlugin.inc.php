<?php

/**
 * @file plugins/importexport/datacite/DataciteExportPlugin.inc.php* Copyright (c) 2003-2019 John Willinsky* @class DataciteExportPlugin
 * @ingroup plugins_importexport_datacite* @brief Datacite XML import/export plugin
 */
import('lib.pkp.classes.plugins.ImportExportPlugin');
import('plugins.importexport.datacite.DataciteExportDeployment');
define('DATACITE_API_RESPONSE_OK', array(200, 201, 302));
define('DATACITE_API_TESTPREFIX', '10.17889');
define('DATACITE_EXPORT_FILE_XML', 0x01);
define('DATACITE_EXPORT_FILE_TAR', 0x02);
define('EXPORT_STATUS_ANY', '');
define('EXPORT_STATUS_NOT_DEPOSITED', 'notDeposited');
define('EXPORT_STATUS_MARKEDREGISTERED', 'markedRegistered');
define('EXPORT_STATUS_REGISTERED', 'registered');
define('EXPORT_ACTION_EXPORT', 'export');
define('EXPORT_ACTION_MARKREGISTERED', 'markRegistered');
define('EXPORT_ACTION_DEPOSIT', 'deposit');
define('EXPORT_CONFIG_ERROR_SETTINGS', 0x02);
define('DOI_EXPORT_REGISTERED_DOI', 'registeredDoi');

class DataciteExportPlugin extends ImportExportPlugin {

	function __construct() {

		parent::__construct();
	}

	function display($args, $request) {

		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);
		$templateMgr->assign('plugin', $this);
		$this->getSettings($request, $templateMgr);
		switch (array_shift($args)) {
			case 'settings':
				$this->updateSettings($request);
			case '':
				import('plugins.importexport.datacite.controllers.grid.DataciteSubmittedListHandler');
				$exportSubmissionsListHandler = new DataciteSubmittedListHandler(array(
					'title' => 'plugins.importexport.datacite.depositedSubmissions',
					'count' => 20,
					'lazyLoad' => false,
				));
				$templateMgr->assign('depositedSubmissionsListData', json_encode($exportSubmissionsListHandler->getConfig()));

				import('plugins.importexport.datacite.controllers.grid.DataciteQueuedListHandler');
				$exportSubmissionsListHandler = new DataciteQueuedListHandler(array(
					'title' => 'plugins.importexport.datacite.queuedSubmissions',
					'count' => 20,
					'inputName' => 'selectedSubmissions[]',
					'lazyLoad' => true,
				));
				$templateMgr->assign('queuedSubmissionsListData', json_encode($exportSubmissionsListHandler->getConfig()));
				$templateMgr->display($this->getTemplateResource('index.tpl'));

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($request->getUser()->getId());

				break;

			case 'export':
				$result = $this->exportSubmissions((array)$request->getUserVar('selectedSubmissions'));

				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($request->getUser()->getId());

				$request->redirect(null, 'management', 'importexport', 'plugin' . urldecode('/') . 'DataciteExportPlugin');

				break;

			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	function getSettings($request, TemplateManager $templateMgr) {

		$press = $request->getPress();
		$api = $this->getSetting($press->getId(), 'api');
		$templateMgr->assign('api', $api);
		$username = $this->getSetting($press->getId(), 'username');
		$templateMgr->assign('username', $username);
		$password = $this->getSetting($press->getId(), 'password');
		$templateMgr->assign('password', $password);
		$testMode = $this->getSetting($press->getId(), 'testMode');
		$templateMgr->assign('testMode', $testMode);

		return array($press, $api, $username, $password, $testMode);
	}

	function updateSettings($request) {

		$contextId = $request->getContext()->getId();
		$userVars = $request->getUserVars();
		if (count($userVars) > 0) {
			$this->updateSetting($contextId, "api", $userVars["api"]);
			$this->updateSetting($contextId, "username", $userVars["username"]);
			$this->updateSetting($contextId, "password", $userVars["password"]);
			$this->updateSetting($contextId, "testMode", $userVars["testMode"]);
		}
	}

	function exportSubmissions($submissionIds) {

		import('lib.pkp.classes.file.FileManager');
		$submissionDao = Application::getSubmissionDAO();
		$request = Application::getRequest();
		$press = $request->getPress();
		$fileManager = new FileManager();
		$result = array();
		foreach ($submissionIds as $submissionId) {
			$deployment = new DataciteExportDeployment($request, $this);
			$submission = $submissionDao->getById($submissionId, $request->getContext()->getId());
			if ($submission->getData('pub-id::doi')) {
				$DOMDocument = new DOMDocument('1.0', 'utf-8');
				$DOMDocument->formatOutput = true;

				$DOMDocument = $deployment->createNodes($DOMDocument, $submission, null, true);
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'datacite-' . $submissionId, $press, '.xml');
				$exportXml = $DOMDocument->saveXML();
				$fileManager->writeFile($exportFileName, $exportXml);
				$result[] = $this->depositXML($submission, $press, $exportFileName, true);
				$fileManager->deleteByPath($exportFileName);

			}

			$chapterDao = DAORegistry::getDAO('ChapterDAO');
			$chaptersList = $chapterDao->getChapters($submissionId);
			$chapters = $chaptersList->toAssociativeArray();
			foreach ($chapters as $chapter) {
				if ($chapter->getData('pub-id::doi')) {
					$DOMDocumentChapter = new DOMDocument('1.0', 'utf-8');
					$DOMDocumentChapter->formatOutput = true;
					$DOMDocumentChapter = $deployment->createNodes($DOMDocumentChapter, $chapter, $submission, false);
					$exportFileName = $this->getExportFileName($this->getExportPath(), 'datacite-' . $submissionId . 'c' . $chapter->getId(), $press, '.xml');
					$exportXml = $DOMDocumentChapter->saveXML();
					$fileManager->writeFile($exportFileName, $exportXml);
					$result[] = $this->depositXML($chapter, $press, $exportFileName, false);
					$fileManager->deleteByPath($exportFileName);
				}
			}


		}

		return $result;
	}

	function depositXML($object, $press, $filename, $isSubmission) {

		$request = Application::getRequest();
		$username = $this->getSetting($press->getId(), 'username');
		$password = $this->getSetting($press->getId(), 'password');
		$api = $this->getSetting($press->getId(), 'api');

		$doi = $object->getData('pub-id::doi');
		assert(!empty($doi));
		if ($this->isTestMode($press)) {
			$doi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		$url = Request::url($press->getPath(), 'catalog', 'book', array($object->getId()));
		assert(!empty($url));
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		assert(is_readable($filename));
		$payload = file_get_contents($filename);
		assert($payload !== false && !empty($payload));
		curl_setopt($curlCh, CURLOPT_URL, $api);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: No response from server."));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if (!in_array($status, DATACITE_API_RESPONSE_OK)) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
			}
		}

		if ($result === true) {
			$payload = "doi=$doi\nurl=$url";
			curl_setopt($curlCh, CURLOPT_URL, $api . 'doi');
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: text/plain;charset=UTF-8'));
			curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
			$response = curl_exec($curlCh);
			if ($response === false) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', 'Registering DOI $doi: No response from server.'));
			} else {
				$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
				if (!in_array($status, DATACITE_API_RESPONSE_OK)) {
					$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
				}
			}
		}
		curl_close($curlCh);
		if ($result === true) {
			if ($this->isTestMode($press)) {
				$registeredDoi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
			}
			$object->setData('pub-id::publisher-id', $registeredDoi);
			if ($isSubmission) {
				$submissionDao = Application::getSubmissionDAO();
				$submissionDao->updateObject($object);
			} else {
				$chapterDao = DAORegistry::getDAO('ChapterDAO');
				$chapterDao->updateObject($object);
			}

		}

		return $response;
	}

	function isTestMode($press) {

		$testMode = $this->getSetting($press->getId(), 'testMode');

		return ($testMode == "on");
	}

	function executeCLI($scriptName, &$args) {

		fatalError('Not implemented.');
	}

	function getDescription() {

		return __('plugins.importexport.datacite.description');
	}

	function getDisplayName() {

		return __('plugins.importexport.datacite.displayName');
	}

	function getName() {

		return 'DataciteExportPlugin';
	}


	function getPluginSettingsPrefix() {

		return 'datacite';
	}

	function register($category, $path, $mainContextId = null) {

		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			$this->import('DataciteExportDeployment');
		}

		return $success;
	}

	function usage($scriptName) {

		fatalError('Not implemented.');
	}

	function setupGridHandler($hookName, $args) {

		$component = $args[0];
		if ($component == 'plugins.generic.datacite.controllers.grid.DataciteGridHandler') {
			import($component);
			DataciteGridHandler::setPlugin($this);

			return true;
		}

		return false;
	}

}
