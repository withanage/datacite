<?php


import('lib.pkp.classes.plugins.ImportExportPlugin');
import('plugins.importexport.datacite.DataciteExportDeployment');
define('DATACITE_API_RESPONSE_OK', array(200, 201, 302, 422, 403));

define('DATACITE_API_REGISTRY', 'https://datacite.org');
define('DATACITE_MDS_REGISTRY', 'https://mds.datacite.org/metadata/');
define('DATACITE_MDS_TEST__REGISTRY', 'https://mds.test.datacite.org/metadata/');


class DataciteExportPlugin extends ImportExportPlugin
{

	function __construct()
	{

		parent::__construct();
	}

	function display($args, $request)
	{

		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);

		$templateMgr->assign('plugin', $this->getName());

		switch (array_shift($args)) {
			case 'settings':
				$this->getSettings($templateMgr);
				$this->updateSettings($request);
				$request->redirect(null, 'management', 'importexport', array('plugin', 'DataciteExportPlugin'));
			case '':
				$this->getSettings($templateMgr);
				$this->depositHandler($request, $templateMgr);
				$templateMgr->display($this->getTemplateResource('index.tpl'));

				break;
			case 'export':
				import('classes.notification.NotificationManager');

				$responses = $this->exportSubmissions((array)$request->getUserVar('submission'));
				$this->createNotifications($request, $responses);
				$request->redirect(null, 'management', 'importexport', array('plugin', 'DataciteExportPlugin'));
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	function getName()
	{

		return 'DataciteExportPlugin';
	}

	function getSettings(TemplateManager $templateMgr)
	{
		$request = Application::getRequest();
		$press = $request->getPress();


		$api = $this->getSetting($press->getId(), 'api');
		$templateMgr->assign('api', $api);
		$username = $this->getSetting($press->getId(), 'username');
		$templateMgr->assign('username', $username);
		$password = $this->getSetting($press->getId(), 'password');
		$templateMgr->assign('password', $password);
		$testMode = $this->getSetting($press->getId(), 'testMode');
		$templateMgr->assign('testMode', $testMode);
		$testPrefix = $this->getSetting($press->getId(), 'testPrefix');
		$templateMgr->assign('testPrefix', $testPrefix);
		$testRegistry = $this->getSetting($press->getId(), 'testRegistry');
		$templateMgr->assign('testRegistry', $testRegistry);
		$testUrl = $this->getSetting($press->getId(), 'testUrl');
		$templateMgr->assign('testUrl', $testUrl);
		$daraMode = $this->getSetting($press->getId(), 'daraMode');
		$templateMgr->assign('daraMode', $daraMode);

		return array($press, $api, $username, $password, $testMode, $testPrefix, $testRegistry, $testUrl, $daraMode);
	}

	function updateSettings($request)
	{

		$contextId = $request->getContext()->getId();
		$userVars = $request->getUserVars();
		if (count($userVars) > 0) {
			$this->updateSetting($contextId, "api", $userVars["api"]);
			$this->updateSetting($contextId, "daraMode", $userVars["daraMode"]);
			$this->updateSetting($contextId, "username", $userVars["username"]);
			$this->updateSetting($contextId, "password", $userVars["password"]);
			$this->updateSetting($contextId, "testMode", $userVars["testMode"]);
			$this->updateSetting($contextId, "testPrefix", $userVars["testPrefix"]);
			$this->updateSetting($contextId, "testRegistry", $userVars["testRegistry"]);
			$this->updateSetting($contextId, "testUrl", $userVars["testUrl"]);
		}
	}

	private function depositHandler($request, TemplateManager $templateMgr)
	{

		$context = $request->getContext();
		$press = $request->getPress();
		$submissionService = ServicesContainer::instance()->get('submission');
		$submissions = $submissionService->getSubmissions($context->getId());
		$itemsQueue = [];
		$itemsDeposited = [];
		$locale = AppLocale::getLocale();
		$registry = $this->getRegistry($press);
		foreach ($submissions as $submission) {
			$submissionId = $submission->getId();
			$doi = $submission->getData('pub-id::doi');
			$publisherID = $submission->getData('pub-id::publisher-id');
			if ($doi and $publisherID) {
				$itemsDeposited[] = array(
					'id' => $submissionId,
					'title' => $submission->getLocalizedTitle($locale),
					'authors' => $submission->getAuthorString($locale),
					'pubId' => $publisherID,
					'registry' => $registry,
				);
			}
			if ($doi and !$publisherID) {
				$itemsQueue[] = array(
					'id' => $submissionId,
					'title' => $submission->getLocalizedTitle($locale),
					'authors' => $submission->getAuthorString($locale),
					'pubId' => $doi,
					'registry' => $registry,
				);
			}
		}
		$templateMgr->assign('itemsQueue', $itemsQueue);
		$templateMgr->assign('itemsSizeQueue', sizeof($itemsQueue));
		$templateMgr->assign('itemsDeposited', $itemsDeposited);
		$templateMgr->assign('itemsSizeDeposited', sizeof($itemsDeposited));
	}

	function getRegistry($press)
	{

		$registry = DATACITE_API_REGISTRY;
		if ($this->isTestMode($press)) {
			$registry = $this->getSetting($press->getId(), 'testRegistry');
		}

		return $registry;
	}

	function isTestMode($press)
	{

		$testMode = $this->getSetting($press->getId(), 'testMode');

		return ($testMode == "on");
	}

	function exportSubmissions($submissionIds)
	{

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
				$response = $this->depositXML($submission, $exportFileName, true);
				$result[$submissionId] = ($response != "") ? $response : "";
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
					$response = $this->depositXML($chapter, $exportFileName, false);
					$result[$submissionId . ".c" . $chapter->getId()] = ($response != "") ? $chapter->getTitle() . " : " . $response : '';
					$fileManager->deleteByPath($exportFileName);
				}
			}
		}

		return $result;
	}

	function depositXML($object, $filename, $isSubmission)
	{


		$doi = $object->getData('pub-id::doi');
		$request = Application::getRequest();
		$press = $request->getPress();

		assert(!empty($doi));
		if ($this->isTestMode($press)) {
			$doi = $this->createTestDOI($request, $doi);
		}

		$url = Request::url($press->getPath(), 'catalog', 'book', array($object->getId()));
		assert(!empty($url));

		$curlCh = curl_init();


		$username = $this->getSetting($press->getId(), 'username');
		$api = $this->getSetting($press->getId(), 'api');
		$password = $this->getSetting($press->getId(), 'password');


		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		if ($this->isDara()) {
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Accept: application/json'));
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
		} else {
			if (array_key_exists('redeposit', $request->getUserVars())) {
				if ($request->getUserVar('redeposit') == 1) {
					$api = ($this->isTestMode($press)) ? DATACITE_MDS_TEST__REGISTRY . $doi : DATACITE_MDS_REGISTRY . $doi;
					curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: text/plain;charset=UTF-8'));
				}
			} else {
				curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.api+json'));
			}

		}

		curl_setopt($curlCh, CURLOPT_VERBOSE, true);
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlCh, CURLOPT_URL, $api);

		assert(is_readable($filename));
		$payload = file_get_contents($filename);
		assert($payload !== false && !empty($payload));

		curl_setopt($curlCh, CURLOPT_VERBOSE, false);
		if ($this->isDara()) {
			curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
			$response = curl_exec($curlCh);
		} else {
			if (array_key_exists('redeposit', $request->getUserVars())) {
				if ($request->getUserVar('redeposit') == 1) {
					curl_setopt($curlCh, CURLOPT_PUT, true);
					curl_setopt($curlCh, CURLOPT_INFILE, $filename);
				}
			} else {
				$datacitePayloadObject = $this->createDatacitePayload($object, $url, $payload, true);
				curl_setopt($curlCh, CURLOPT_POSTFIELDS, $datacitePayloadObject);
				self::writeLog( " :: ".$this->isDara(), 'ERROR');
			}
			$response = curl_exec($curlCh);

		}


		$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
		curl_close($curlCh);


		$this->setDOI($object, $isSubmission, $status, $response, $press, $request, $doi);

		return $response;
	}

	public function createTestDOI($request, $doi)
	{
		return PKPString::regexp_replace('#^[^/]+/#', $this->getDataciteAPITestPrefix() . '/', $doi);
	}

	function getDataciteAPITestPrefix()
	{
		$request = Application::getRequest();
		$press = $request->getPress();

		return $this->getSetting($press->getId(), 'testPrefix');
	}

	function isDara()
	{
		$request = Application::getRequest();
		$press = $request->getPress();
		$daraMode = $this->getSetting($press->getId(), 'daraMode');

		return ($daraMode == "on");
	}

	function createDatacitePayload($obj, $url, $payload, $payLoadAvailable = false)
	{

		{
			$doi = $obj->getStoredPubId("doi");
			$request = Application::getRequest();
			$press = $request->getPress();
			if ($this->isTestMode($press)) {
				$doi = $this->createTestDOI($request, $doi);
			}
			if ($payLoadAvailable) {
				$jsonPayload = array("data" => (array('id' => $doi, 'type' => "dois", 'attributes' => array("event" => "publish", "doi" => $doi, "url" => $url, "xml" => base64_encode($payload)))));
			} else {
				$jsonPayload = array("data" => (array('type' => "dois", 'attributes' => array("doi" => $doi))));
			}

			return json_encode($jsonPayload, JSON_UNESCAPED_SLASHES);

		}

	}

	private function setDOI($object, $isSubmission, $status, $response, $press, $request, $doi): void

	{
		$result = true;
		if (!in_array($status, DATACITE_API_RESPONSE_OK)) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', $response));
		}


		if ($result === true) {
			if ($this->isTestMode($press)) {
				$doi = $this->createTestDOI($request, $doi);
			}
			$object->setData('pub-id::publisher-id', $doi);
			if ($isSubmission) {
				$submissionDao = Application::getSubmissionDAO();
				$submissionDao->updateObject($object);
			} else {
				$chapterDao = DAORegistry::getDAO('ChapterDAO');
				$chapterDao->updateObject($object);
			}
		}
	}

	private function createNotifications($request, array $responses)
	{

		$success = 1;
		$notification = "";
		$notificationManager = new NotificationManager();
		foreach ($responses as $submission => $error) {
			$result = json_decode(str_replace("\n", "", $error), true);

			if (isset($result["errors"])) {
				if ($this->isDara()) {
					$detail = $result["errors"]["detail"];
					$notification .= str_replace('"', '', $detail);
				} else {
					$detail = $result["errors"][0]["title"];
				}
				$success = 0;
				self::writeLog($submission . " ::  " . $detail, 'ERROR');
				$notificationManager->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => $detail));
			}
		}

		if ($success == 1) {
			$detail = (strpos(implode($responses), 'OK') !== false) ? implode($responses) : "Successfully deposited";
			$notificationManager->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $detail));
		}
	}

	private static function writeLog($message, $level)
	{

		$fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
		error_log("$fineStamp $level $message\n", 3, self::logFilePath());
	}

	public static function logFilePath()
	{

		return Config::getVar('files', 'files_dir') . '/DATACITE_ERROR.log';
	}

	function setupGridHandler($hookName, $args)
	{

		import('plugins.generic.customLocale.controllers.grid.DataciteSubmittedListHandler');
		DataciteSubmittedListHandler::setPlugin($this);

		return true;
	}

	function executeCLI($scriptName, &$args)
	{

		fatalError('Not implemented.');
	}

	function getDescription()
	{

		return __('plugins.importexport.datacite.description');
	}

	function getDisplayName()
	{

		return __('plugins.importexport.datacite.displayName');
	}

	function getPluginSettingsPrefix()
	{

		return 'datacite';
	}

	function register($category, $path, $mainContextId = null)
	{

		HookRegistry::register('PKPLocale::registerLocaleFile', array(&$this, 'addCustomLocale'));
		HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
		HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));
		HookRegistry::register('LoadHandler', array($this, 'handleLoadRequest'));
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			$this->import('DataciteExportDeployment');
		}

		return $success;
	}

	function usage($scriptName)
	{

		fatalError('Not implemented.');
	}

}
