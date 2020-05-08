<?php

/**
 * @file plugins/importexport/datacite/DataciteExportPlugin.inc.php* Copyright (c) 2003-2019 John Willinsky* @class DataciteExportPlugin
 * @ingroup plugins_importexport_datacite* @brief Datacite XML import/export plugin
 */
import('lib.pkp.classes.plugins.ImportExportPlugin');
import('plugins.importexport.datacite.DataciteExportDeployment');


define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');

define('DATACITE_API_TESTPREFIX', '10.5072');
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


class DataciteExportPlugin extends ImportExportPlugin {


	function __construct() {

		parent::__construct();
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

	function getName() {

		return 'DataciteExportPlugin';
	}

	function getDisplayName() {

		return __('plugins.importexport.datacite.displayName');
	}

	function getDescription() {

		return __('plugins.importexport.datacite.description');
	}

	function getPluginSettingsPrefix() {

		return 'datacite';
	}

	function isTestMode($press) {
		$testMode = $this->getSetting($press->getId(), 'testMode');
		return ( $testMode== "on");
	}
	function updateSettings($request) {
		$contextId = $request->getContext()->getId();
		$userVars = $request->getUserVars();
		$this->updateSetting($contextId,"username",$userVars["username"]);
		$this->updateSetting($contextId,"password",$userVars["password"]);
		$this->updateSetting($contextId,"testMode",$userVars["testMode"]);

	}

	function display($args, $request) {

		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);
		$templateMgr->assign('plugin', $this);
		switch (array_shift($args)) {

			case 'settings':
			$this->updateSettings($request);

			case '':
				import('lib.pkp.controllers.list.submissions.SelectSubmissionsListHandler');
				$press = $request->getPress();

				$username = $this->getSetting($press->getId(), 'username');
				$templateMgr->assign('username',$username);
				$password = $this->getSetting($press->getId(), 'password');
				$templateMgr->assign('password',$password);
				$testMode = $this->getSetting($press->getId(), 'testMode');
				$templateMgr->assign('testMode',$testMode);
				$exportSubmissionsListHandler = new SelectSubmissionsListHandler(array(
					'title' => 'plugins.importexport.datacite.exportSubmissionsSelect',
					'count' => 20,
					'inputName' => 'selectedSubmissions[]',
					'lazyLoad' => true,
				));
				$templateMgr->assign('exportSubmissionsListData', json_encode($exportSubmissionsListHandler->getConfig()));

				$templateMgr->display($this->getTemplateResource('index.tpl'));
				break;
			case 'export':

				$this->exportSubmissions(
					(array)$request->getUserVar('selectedSubmissions')
				);

				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	function exportSubmissions($submissionIds) {

		import('lib.pkp.classes.file.FileManager');
		$submissionDao = Application::getSubmissionDAO();
		$request = Application::getRequest();
		$press = $request->getPress();

		$fileManager = new FileManager();

		foreach ($submissionIds as $submissionId) {
			$deployment = new DataciteExportDeployment($request, $this);
			$submission = $submissionDao->getById($submissionId, $request->getContext()->getId());

			$DOMDocument = new DOMDocument('1.0', 'utf-8');
			$DOMDocument->formatOutput = true;
			$DOMDocument = $deployment->createNodes($DOMDocument, $submission);

			$exportFileName = $this->getExportFileName($this->getExportPath(), 'datacite-' . $submissionId, $press, '.xml');

			$exportXml = $DOMDocument->saveXML();

			$fileManager->writeFile($exportFileName, $exportXml);

			$result = $this->depositXML($submission, $press, $exportFileName);
			if (is_array($result)) {
				$resultErrors[] = $result;
			}

			//$fileManager->deleteByPath($exportFileName);

		}


	}

	function getDepositStatusSettingName() {
		return $this->getPluginSettingsPrefix().'::status';
	}

	function depositXML($submission, $press, $filename) {
		$request = Application::getRequest();

		$doi = $submission->getData('pub-id::doi');
		assert(!empty($doi));
		if ($this->isTestMode($press)) {
			$doi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		$url = Request::url($press->getPath(), 'catalog', 'book', array($submission->getId()));

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

		$username = $this->getSetting($press->getId(), 'username');
		$password = $this->getSetting($press->getId(), 'password');
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
		// Transmit meta-data.
		assert(is_readable($filename));
		$payload = file_get_contents($filename);
		assert($payload !== false && !empty($payload));
		curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'metadata');
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: No response from server."));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status != DATACITE_API_RESPONSE_OK) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
			}
		}
		// Mint a DOI.
		if ($result === true) {
			$payload = "doi=$doi\nurl=$url";
			curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'doi');
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: text/plain;charset=UTF-8'));
			curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
			$response = curl_exec($curlCh);
			if ($response === false) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', 'Registering DOI $doi: No response from server.'));
			} else {
				$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
				if ($status != DATACITE_API_RESPONSE_OK) {
					$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
				}
			}
		}
		curl_close($curlCh);
		if ($result === true) {
			$submission->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
			$this->saveRegisteredDoi($press, $submission, DATACITE_API_TESTPREFIX);
		}
		return $result;
	}

	function saveRegisteredDoi($press, $submission, $testPrefix = DATACITE_API_TESTPREFIX) {
		$registeredDoi = $submission->getStoredPubId('doi');
		assert(!empty($registeredDoi));
		if ($this->isTestMode($press)) {
			$registeredDoi = PKPString::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
		}
		$submission->setData($this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI, $registeredDoi);
		$this->updateObject($submission);
	}


	function executeCLI($scriptName, &$args) {

		fatalError('Not implemented.');
	}

	function usage($scriptName) {

		fatalError('Not implemented.');
	}

}
