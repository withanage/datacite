<?php
/**
 * @defgroup plugins_importexport_datacite DataCite export plugin
 */

/**
 * @file plugins/importexport/datacite/DataciteExportDeployment.inc.php*
 * @class DataciteExportDeployment
 * @ingroup plugins_importexport_datacite* @brief Base class configuring the datacite export process to an
 * application's specifics.
 */

define('DATACITE_XMLNS', 'http://da-ra.de/schema/kernel-4');
define('DATACITE_XMLNS_XSI', 'http://www.w3.org/2001/XMLSchema-instance');
define('DATACITE_XSI_SCHEMAVERSION', '4');
define('DATACITE_XSI_SCHEMALOCATION', 'http://da-ra.de/schema/kernel-4 http://www.da-ra.de/fileadmin/media/da-ra.de/Technik/4.0/dara.xsd');

import('lib.pkp.classes.plugins.importexport.PKPImportExportDeployment');

class DataciteExportDeployment extends PKPImportExportDeployment {

	var $_context;

	var $_plugin;

	function __construct($request, $plugin) {

		$context = $request->getContext();
		parent::__construct($context, $plugin);
		$this->_context = $context;
		$this->_plugin = $plugin;
	}

	function createNodes($documentNode, $submission) {

		$documentNode = $this->createRootNode($documentNode);
		$documentNode = $this->createResourceType($documentNode);
		$documentNode = $this->createResourceIdentifier($documentNode, $submission);
		$documentNode = $this->createTitles($documentNode, $submission);
		$documentNode = $this->createOtherTitles($documentNode, $submission);
		$documentNode = $this->createCreators($documentNode, $submission);
		$documentNode = $this->createDataURLs($documentNode, $submission);
		$documentNode = $this->createDoiProposal($documentNode, $submission);
		$documentNode = $this->createPublicationYear($documentNode, $submission);
		$documentNode = $this->createPublicationPlace($documentNode);
		$documentNode = $this->createRelations($documentNode, $submission);

		return $documentNode;
	}

	function createRootNode($documentNode) {

		$rootNode = $documentNode->createElementNS($this->getNamespace(), $this->getRootElementName());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $this->getXmlSchemaInstance());
		$rootNode->setAttribute('xsi:schemaLocation', $this->getNamespace() . ' ' . $this->getSchemaFilename());
		$documentNode->appendChild($rootNode);

		return $documentNode;
	}

	function getNamespace() {

		return DATACITE_XMLNS;
	}

	function getRootElementName() {

		return 'resource';
	}

	function getXmlSchemaInstance() {

		return DATACITE_XMLNS_XSI;
	}

	function getSchemaFilename() {

		return $this->getXmlSchemaLocation();
	}

	function getXmlSchemaLocation() {

		return DATACITE_XSI_SCHEMALOCATION;
	}

	function createResourceType($documentNode) {

		$e = $documentNode->createElement("resourceType", "Text");
		$documentNode->documentElement->appendChild($e);

		return $documentNode;
	}

	function createResourceIdentifier($documentNode, $submission) {

		$e = $documentNode->createElement("resourceIdentifier");

		$pubId = $submission->getData('pub-id::doi');
		if (isset($pubId)) {
			$identifier = $documentNode->createElement("identifier", $pubId);
			$e->appendChild($identifier);
		}

		$currentVersion = $documentNode->createElement("currentVersion", 1);
		$e->appendChild($currentVersion);
		$documentNode->documentElement->appendChild($e);

		return $documentNode;
	}

	function createTitles($documentNode, $submission) {

		$locale = $submission->getData('locale');
		$titles = $documentNode->createElement("titles");
		$language = $documentNode->createElement("language", substr($locale, 0, 2));
		$title = $documentNode->createElement("title", $submission->getLocalizedTitle($locale));
		$titles->appendChild($language);
		$titles->appendChild($title);
		$documentNode->documentElement->appendChild($titles);

		return $documentNode;
	}

	function createOtherTitles($documentNode, $submission) {

		$locale = $submission->getData('locale');
		$localizedSubtitle = $submission->getLocalizedSubtitle($locale);
		if (strlen($localizedSubtitle) > 0) {
			$otherTitles = $documentNode->createElement("otherTitles");

			$otherTitle = $documentNode->createElement("otherTitle");
			$language = $documentNode->createElement("language", substr($locale, 0, 2));
			$titleName = $documentNode->createElement("titleName", $localizedSubtitle);
			$titleType = $documentNode->createElement("titleType", "Subtitle");

			$otherTitle->appendChild($language);
			$otherTitle->appendChild($titleName);
			$otherTitle->appendChild($titleType);
			$otherTitles->appendChild($otherTitle);

			$documentNode->documentElement->appendChild($otherTitles);
		}
		return $documentNode;
	}

	function createCreators($documentNode, $submission) {

		$locale = $submission->getData('locale');
		$creators = $documentNode->createElement("creators");
		$authors = $submission->getAuthors();
		foreach ($authors as $author) {
			$creator = $documentNode->createElement("creator");
			$person = $documentNode->createElement("person");
			$firstName = $documentNode->createElement("firstName", $author->getGivenName($locale));
			$person->appendChild($firstName);
			$lastName = $documentNode->createElement("lastName", $author->getFamilyName($locale));
			$person->appendChild($lastName);
			$creator->appendChild($person);
			$creators->appendChild($creator);
		}
		$documentNode->documentElement->appendChild($creators);

		return $documentNode;
	}

	function createDataURLs($documentNode, $submission) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$dataURLPath = Request::url($press->getPath(), 'catalog', 'book', array($submission->getId()));
		$dataURLs = $documentNode->createElement("dataURLs");
		$dataURL = $documentNode->createElement("dataURL", $dataURLPath);
		$dataURLs->appendChild($dataURL);
		$documentNode->documentElement->appendChild($dataURLs);

		return $documentNode;

	}

	function createDoiProposal($documentNode, $submission) {

		$pubId = $submission->getData('pub-id::doi');
		if (isset($pubId)) {
			$doiProposal = $documentNode->createElement("doiProposal", $pubId);
			$documentNode->documentElement->appendChild($doiProposal);


		}

		return $documentNode;
	}

	function createPublicationYear($documentNode, $submission) {

		$date = $submission->getDatePublished();
		$publicationDate = $documentNode->createElement("publicationDate");
		$year = $documentNode->createElement("year", substr($date, 0, 4));
		$publicationDate->appendChild($year);
		$documentNode->documentElement->appendChild($publicationDate);

		return $documentNode;
	}

	function createPublicationPlace($documentNode) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$location = $press->getData('location');
		$publicationPlace = $documentNode->createElement("publicationPlace",$location);
		$documentNode->documentElement->appendChild($publicationPlace);

		return $documentNode;

	}

	function createAvailability($documentNode) {

		$availability = $documentNode->createElement("availability");
		$availabilityType = $documentNode->createElement("availabilityTpe", "Download");
		$availability->appendChild($availabilityType);
		$documentNode->documentElement->appendChild($availability);

		return $documentNode;

	}

	function createRelations($documentNode, $submission) {

		$relationCount = 0;
		$chapterDao = DAORegistry::getDAO('ChapterDAO');
		$chaptersList = $chapterDao->getChapters($submission->getId());
		$chapters = $chaptersList->toAssociativeArray();

		$relations = $documentNode->createElement("relations");
		foreach ($chapters as $chapter) {
			$chapterDoi = $chapter->getStoredPubId('doi');
			if (isset($chapterDoi)) {

				$relation = $documentNode->createElement("relation");
				$identifier = $documentNode->createElement("identifier",$chapterDoi);
				$identifierType = $documentNode->createElement("identifierType","DOI");
				$relationType = $documentNode->createElement("relationType","HasPart");
				$resourceType = $documentNode->createElement("resourceType","Text");
				$relation->appendChild($identifier);
				$relation->appendChild($identifierType);
				$relation->appendChild($relationType);
				$relation->appendChild($resourceType);
				$relations->appendChild($relation);

				$relationCount += 1;


			}
		}
		if ($relationCount> 0) {
			$documentNode->documentElement->appendChild($relations);
		}
		return $documentNode;
	}


	function getContext() {

		return $this->_context;
	}

	function setContext($context) {

		$this->_context = $context;
	}

	function getPlugin() {

		return $this->_plugin;
	}

	function setPlugin($plugin) {

		$this->_plugin = $plugin;
	}

	function getXmlSchemaVersion() {

		return DATACITE_XSI_SCHEMAVERSION;
	}

}
