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

define('DATACITE_EXPORT_FILE_XML', 0x01);
define('DATACITE_EXPORT_FILE_TAR', 0x02);


class DataciteExportPlugin extends ImportExportPlugin {

	/**
	 * Constructor
	 */
	function __construct() {

		parent::__construct();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {

		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			$this->import('DataciteExportDeployment');
		}

		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {

		return 'DataciteExportPlugin';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {

		return __('plugins.importexport.datacite.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {

		return __('plugins.importexport.datacite.description');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {

		return 'datacite';
	}
	function updateSettings($request) {
		$contextId = $request->getContext()->getId();
		$userVars = $request->getUserVars();
		$this->updateSetting($contextId,"username",$userVars["username"]);
		$this->updateSetting($contextId,"password",$userVars["password"]);
		$this->updateSetting($contextId,"testMode",$userVars["testMode"]);

	}


	/**
	 * Display the plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {

		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);
		$templateMgr->assign('plugin', $this);
		switch (array_shift($args)) {
			case 'index':
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
			$ded = new DataciteExportDeployment($request, $this);
			$submission = $submissionDao->getById($submissionId, $request->getContext()->getId());

			$DOMDocument = new DOMDocument('1.0', 'utf-8');
			$DOMDocument->formatOutput = true;
			$DOMDocument = $ded->createNodes($DOMDocument, $submission);

			$exportFileName = $this->getExportFileName($this->getExportPath(), 'datacite-' . $submissionId, $press, '.xml');

			$contents = $DOMDocument->saveXML();
			$fileManager->writeFile($exportFileName, $contents);
			//$fileManager->downloadByPath($exportFileName);
			//$fileManager->deleteByPath($exportFileName);

		}


	}

	/**
	 * @copydoc ImportExportPlugin::executeCLI
	 */
	function executeCLI($scriptName, &$args) {

		fatalError('Not implemented.');
	}

	/**
	 * @copydoc ImportExportPlugin::usage
	 */
	function usage($scriptName) {

		fatalError('Not implemented.');
	}

}
