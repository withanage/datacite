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

	function createNodes($documentNode, $object, $parent, $isSubmission) {

		$documentNode = $this->createRootNode($documentNode);
		$documentNode = $this->createResourceType($documentNode);
		$documentNode = $this->createResourceIdentifier($documentNode, $object);
		$documentNode = $this->createTitles($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createOtherTitles($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createAuthors($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createDataURLs($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createDoiProposal($documentNode, $object);
		$documentNode = $this->createPublicationYear($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createPublicationPlace($documentNode);
		$documentNode = $this->createPublisher($documentNode);
		$documentNode = $this->createAvailability($documentNode, $object);
		if ($isSubmission == true) {
			$documentNode = $this->createRelationsOfChildren($documentNode, $object);
		} else {
			$documentNode = $this->createRelationsOfParent($documentNode, $parent);
		}

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

	function createResourceIdentifier($documentNode, $object) {

		$e = $documentNode->createElement("resourceIdentifier");

		$pubId = $object->getData('pub-id::doi');
		if (isset($pubId)) {
			$pubIdSuffix = preg_replace('/^[\d]+(.)[\d]+(\/)/', '', $pubId);
			$identifier = $documentNode->createElement("identifier", $pubIdSuffix);
			$e->appendChild($identifier);
		}

		$currentVersion = $documentNode->createElement("currentVersion", 1);
		$e->appendChild($currentVersion);
		$documentNode->documentElement->appendChild($e);

		return $documentNode;
	}

	function createTitles($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$titles = $documentNode->createElement("titles");
		$language = $documentNode->createElement("language", substr($locale, 0, 2));
		$title = $documentNode->createElement("title");
		$titleName = $documentNode->createElement("titleName", $object->getLocalizedTitle($locale));
		$title->appendChild($titleName);
		$title->appendChild($language);
		$titles->appendChild($title);
		$documentNode->documentElement->appendChild($titles);

		return $documentNode;
	}

	function createOtherTitles($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedSubtitle = $object->getLocalizedSubtitle($locale);
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

	function createAuthors($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$creators = $documentNode->createElement("creators");
		$authors = $object->getAuthors();
		if ($isSubmission == true) {
			foreach ($authors as $author) {
				$creator = $this->createAuthor($documentNode, $author, $locale);
				$creators->appendChild($creator);
				$documentNode->documentElement->appendChild($creators);
			}
		} else {
			$chapterAuthorDao = DAORegistry::getDAO('ChapterAuthorDAO');
			$chapterAuthors = $chapterAuthorDao->getAuthors($object->getMonographId(), $object->getId());
			while ($author = $chapterAuthors->next()) {
				$creator = $this->createAuthor($documentNode, $author, $locale);
				$creators->appendChild($creator);
				$documentNode->documentElement->appendChild($creators);

			}


		}

		return $documentNode;
	}

	private function createAuthor($documentNode, $author, $locale) {

		$creator = $documentNode->createElement("creator");
		$person = $documentNode->createElement("person");
		$firstName = $documentNode->createElement("firstName", $author->getGivenName($locale));
		$person->appendChild($firstName);
		$lastName = $documentNode->createElement("lastName", $author->getFamilyName($locale));
		$person->appendChild($lastName);
		$creator->appendChild($person);

		return $creator;
	}

	function createDataURLs($documentNode, $object, $parent, $isSubmission) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$urlPart = ($isSubmission == true) ? array($object->getId()) : array($parent->getId(), 'c' . $object->getId());
		//$dataURLPath = Request::url($press->getPath(), 'catalog', 'book', array($object->getId()));
		$dataURLPath = "https://books.ub.uni-heidelberg.de/index.php/arthistoricum/catalog/book/" . implode('/', $urlPart);
		$dataURLs = $documentNode->createElement("dataURLs");
		$dataURL = $documentNode->createElement("dataURL", $dataURLPath);
		$dataURLs->appendChild($dataURL);
		$documentNode->documentElement->appendChild($dataURLs);

		return $documentNode;

	}

	function createDoiProposal($documentNode, $object) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', DATACITE_API_TESTPREFIX, $pubId);
			}
			$doiProposal = $documentNode->createElement("doiProposal", $pubId);
			$documentNode->documentElement->appendChild($doiProposal);


		}

		return $documentNode;
	}

	function getPlugin() {

		return $this->_plugin;
	}

	function setPlugin($plugin) {

		$this->_plugin = $plugin;
	}

	function createPublicationYear($documentNode, $object, $parent, $isSubmission) {

		$date = $object->getDatePublished();
		if ($date == null) $date = $object->getDateSubmitted();

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
		$publicationPlace = $documentNode->createElement("publicationPlace", $location);
		$documentNode->documentElement->appendChild($publicationPlace);

		return $documentNode;

	}

	function createPublisher($documentNode) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$publisher = $documentNode->createElement("publisher");
		$institution = $documentNode->createElement("institution");
		$institutionName = $documentNode->createElement("institutionName", $press->getData('publisher'));

		$institution->appendChild($institutionName);
		$publisher->appendChild($institution);
		$documentNode->documentElement->appendChild($publisher);

		return $documentNode;

	}

	function createAvailability($documentNode) {

		$availability = $documentNode->createElement("availability");
		$availabilityType = $documentNode->createElement("availabilityType", "Download");
		$availability->appendChild($availabilityType);
		$documentNode->documentElement->appendChild($availability);

		return $documentNode;

	}

	function createRelationsOfChildren($documentNode, $object) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$relationCount = 0;
		$chapterDao = DAORegistry::getDAO('ChapterDAO');
		$chaptersList = $chapterDao->getChapters($object->getId());
		$chapters = $chaptersList->toAssociativeArray();

		$relations = $documentNode->createElement("relations");
		foreach ($chapters as $chapter) {
			$pubId = $chapter->getStoredPubId('doi');
			if (isset($pubId)) {

				if ($this->getPlugin()->isTestMode($press)) {
					$pubId = preg_replace('/^[\d]+(.)[\d]+/', DATACITE_API_TESTPREFIX, $pubId);
				}
				$relation = $documentNode->createElement("relation");
				$identifier = $documentNode->createElement("identifier", $pubId);
				$identifierType = $documentNode->createElement("identifierType", "DOI");
				$relationType = $documentNode->createElement("relationType", "HasPart");
				$resourceType = $documentNode->createElement("resourceType", "Text");
				$relation->appendChild($identifier);
				$relation->appendChild($identifierType);
				$relation->appendChild($relationType);
				$relation->appendChild($resourceType);
				$relations->appendChild($relation);

				$relationCount += 1;


			}
		}
		if ($relationCount > 0) {
			$documentNode->documentElement->appendChild($relations);
		}

		return $documentNode;
	}

	function createRelationsOfParent($documentNode, $parent) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$relations = $documentNode->createElement("relations");
		$pubId = $parent->getStoredPubId('doi');
		if (isset($pubId)) {

			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', DATACITE_API_TESTPREFIX, $pubId);
			}
			$relation = $documentNode->createElement("relation");
			$identifier = $documentNode->createElement("identifier", $pubId);
			$identifierType = $documentNode->createElement("identifierType", "DOI");
			$relationType = $documentNode->createElement("relationType", "IsPartOf");
			$resourceType = $documentNode->createElement("resourceType", "Text");
			$relation->appendChild($identifier);
			$relation->appendChild($identifierType);
			$relation->appendChild($relationType);
			$relation->appendChild($resourceType);
			$relations->appendChild($relation);

			$documentNode->documentElement->appendChild($relations);

			return $documentNode;
		}
	}

	function getContext() {

		return $this->_context;
	}

	function setContext($context) {

		$this->_context = $context;
	}

	function getXmlSchemaVersion() {

		return DATACITE_XSI_SCHEMAVERSION;
	}


}
