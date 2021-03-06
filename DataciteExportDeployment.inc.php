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

define('DARA_XMLNS', 'http://da-ra.de/schema/kernel-4');
define('DATACITE_XMLNS', 'http://datacite.org/schema/kernel-4');
define('XMLNS_XSI', 'http://www.w3.org/2001/XMLSchema-instance');
define('DATACITE_XSI_SCHEMA_LOCATION', 'http://schema.datacite.org/meta/kernel-4.3/metadata.xsd');
define('DARA_XSI_SCHEMA_LOCATION', 'http://www.da-ra.de/fileadmin/media/da-ra.de/Technik/4.0/dara.xsd');

import('lib.pkp.classes.plugins.importexport.PKPImportExportDeployment');

class DataciteExportDeployment extends PKPImportExportDeployment
{

	var $_context;

	var $_plugin;

	private $givenName;

	function __construct($request, $plugin)
	{

		$context = $request->getContext();
		parent::__construct($context, $plugin);
		$this->_context = $context;
		$this->_plugin = $plugin;
	}

	function createNodes($documentNode, $object, $parent, $isSubmission)
	{

		$documentNode = $this->createRootNode($documentNode);
		$documentNode = $this->createResourceType($documentNode, $isSubmission);
		$documentNode = $this->createResourceIdentifier($documentNode, $object);
		$documentNode = $this->createTitles($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createAuthors($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createPublicationYear($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createPublisher($documentNode);

		if ($this->getPlugin()->isDara()) {
			$documentNode = $this->createOtherTitles($documentNode, $object, $parent, $isSubmission);
			$documentNode = $this->createAvailability($documentNode, $object);
			$documentNode = $this->createPublicationPlace($documentNode);
			$documentNode = $this->createDataURLs($documentNode, $object, $parent, $isSubmission);
			$documentNode = $this->createDoiProposal($documentNode, $object);
			if ($isSubmission == true) {
				$documentNode = $this->createRelationsOfChildren($documentNode, $object);
			} else {
				$documentNode = $this->createRelationsOfParent($documentNode, $parent);
			}

		}


		return $documentNode;
	}

	function createRootNode($documentNode)
	{

		$rootNode = $documentNode->createElementNS($this->getNamespace(), $this->getRootElementName());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $this->getXmlSchemaInstance());
		$rootNode->setAttribute('xsi:schemaLocation', $this->getNamespace() . ' ' . $this->getSchemaLocation());
		$documentNode->appendChild($rootNode);

		return $documentNode;
	}

	function getNamespace()
	{
		return ($this->getPlugin()->isDara()) ? DARA_XMLNS : DATACITE_XMLNS;
	}

	function getPlugin()
	{

		return $this->_plugin;
	}

	function setPlugin($plugin)
	{

		$this->_plugin = $plugin;
	}

	function getRootElementName()
	{

		return 'resource';
	}

	function getXmlSchemaInstance()
	{

		return XMLNS_XSI;
	}

	function getSchemaLocation()
	{
		return ($this->getPlugin()->isDara()) ? DARA_XSI_SCHEMA_LOCATION : DATACITE_XSI_SCHEMA_LOCATION;
	}

	function createResourceType($documentNode, $isSubmission)
	{
		if ($this->getPlugin()->isDara()) {
			$e = $documentNode->createElement("resourceType", "Text");
			$documentNode->documentElement->appendChild($e);
		} else {
			$type = ($isSubmission == true) ? 'Monograph' : 'Chapter';
			$e = $documentNode->createElement("resourceType", $type);
			$e->setAttribute('resourceTypeGeneral', 'Text');
			$documentNode->documentElement->appendChild($e);
		}
		return $documentNode;
	}

	function createResourceIdentifier($documentNode, $object)
	{


		$request = Application::getRequest();
		$press = $request->getPress();
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this->getPlugin()->getSetting($press->getId(), "testPrefix"), $pubId);
			}
		}

		if ($this->getPlugin()->isDara()) {
			$e = $documentNode->createElement("resourceIdentifier");
			if (isset($pubId)) {
				$identifier = $documentNode->createElement("identifier", $pubId);
				$e->appendChild($identifier);
			}
			$currentVersion = $documentNode->createElement("currentVersion", 1);
			$e->appendChild($currentVersion);
			$documentNode->documentElement->appendChild($e);
		} else {
			$identifier = $documentNode->createElement("identifier", $pubId);
			$identifier->setAttribute("identifierType", "DOI");
			$documentNode->documentElement->appendChild($identifier);

		}

		return $documentNode;
	}

	function createTitles($documentNode, $object, $parent, $isSubmission)
	{

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedTitle = $object->getLocalizedTitle($locale);
		$language = $documentNode->createElement("language", substr($locale, 0, 2));
		$titles = $documentNode->createElement("titles");
		$titleValue = $this->xmlEscape($localizedTitle);
		if ($this->getPlugin()->isDara()) {
			$title = $documentNode->createElement("title");
			$titleName = $documentNode->createElement("titleName", $titleValue);
			$title->appendChild($titleName);
			$title->appendChild($language);
			$titles->appendChild($title);
		} else {
			$title = $documentNode->createElement("title", $titleValue);
			$title->setAttribute("xml:lang", str_replace_first("_", "-", $locale));
			$titles->appendChild($title);
		}
		$documentNode->documentElement->appendChild($titles);

		return $documentNode;
	}

	function xmlEscape($value)
	{
		return XMLNode::xmlentities($value, ENT_NOQUOTES);
	}

	function createAuthors($documentNode, $object, $parent, $isSubmission)
	{

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$creators = $documentNode->createElement("creators");
		$authors = $object->getAuthors();
		if ($isSubmission == true) {
			foreach ($authors as $author) {
				$creator = $this->createAuthor($documentNode, $author, $locale);
				if ($creator) {
					$creators->appendChild($creator);
					$documentNode->documentElement->appendChild($creators);
				}
			}
		} else {
			$chapterAuthorDao = DAORegistry::getDAO('ChapterAuthorDAO');
			$chapterAuthors = $chapterAuthorDao->getAuthors($object->getMonographId(), $object->getId());
			while ($author = $chapterAuthors->next()) {
				$creator = $this->createAuthor($documentNode, $author, $locale);
				if ($creator) {
					$creators->appendChild($creator);
					$documentNode->documentElement->appendChild($creators);
				}

			}


		}

		return $documentNode;
	}

	function createAuthor($documentNode, $author, $locale)
	{

		$creator = $documentNode->createElement("creator");
		$person = $documentNode->createElement("person");
		$familyName = $author->getFamilyName($locale);
		$givenName = $author->getGivenName($locale);
		if ($familyName == '' && $givenName == '') {
			return null;
		}
		if ($this->getPlugin()->isDara()) {
			if ($familyName != '') {
				$lastName = $documentNode->createElement("lastName", $familyName);
				$firstName = $documentNode->createElement("firstName", $givenName);
				$person->appendChild($firstName);
				$person->appendChild($lastName);
				$creator->appendChild($person);
			} else {
				$institution = $documentNode->createElement("institution");
				$institutionName = $documentNode->createElement("institutionName", $this->xmlEscape($givenName));
				$institution->appendChild($institutionName);
				$creator->appendChild($institution);
			}
		} else {
			$creatorName = $documentNode->createElement("creatorName", $familyName . ', ' . $givenName);
			$creatorName->setAttribute("nameType", "Personal");
			$creator->appendChild($creatorName);
		}


		return $creator;
	}

	function createPublicationYear($documentNode, $object, $parent, $isSubmission)
	{

		$date = $object->getDatePublished();
		if ($date == null) {
			if ($isSubmission == true) {
				$date = $object->getDateSubmitted();
			} else {
				$date = ($parent->getDatePublished()) ? $parent->getDatePublished() : $parent->getDateSubmitted();
			}
		}
		if ($this->getPlugin()->isDara()) {
			$publicationDate = $documentNode->createElement("publicationDate");
			$year = $documentNode->createElement("year", substr($date, 0, 4));
			$publicationDate->appendChild($year);
			$documentNode->documentElement->appendChild($publicationDate);
		} else {
			$publicationYear = $documentNode->createElement("publicationYear", substr($date, 0, 4));
			$documentNode->documentElement->appendChild($publicationYear);
		}

		return $documentNode;
	}

	function createPublisher($documentNode)
	{

		$request = Application::getRequest();
		$press = $request->getPress();
		if ($this->getPlugin()->isDara()) {
			$publisher = $documentNode->createElement("publisher");
			$institution = $documentNode->createElement("institution");
			$institutionName = $documentNode->createElement("institutionName", $press->getData('publisher'));

			$institution->appendChild($institutionName);
			$publisher->appendChild($institution);
		} else {
			$publisher = $documentNode->createElement("publisher", $press->getData('publisher'));
		}

		$documentNode->documentElement->appendChild($publisher);

		return $documentNode;

	}

	function createOtherTitles($documentNode, $object, $parent, $isSubmission)
	{

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedSubtitle = $object->getLocalizedSubtitle($locale);
		if (strlen($localizedSubtitle) > 0) {
			$otherTitles = $documentNode->createElement("otherTitles");

			$otherTitle = $documentNode->createElement("otherTitle");
			$language = $documentNode->createElement("language", substr($locale, 0, 2));
			$titleName = $documentNode->createElement("titleName", $this->xmlEscape($localizedSubtitle));
			$titleType = $documentNode->createElement("titleType", "Subtitle");

			$otherTitle->appendChild($language);
			$otherTitle->appendChild($titleName);
			$otherTitle->appendChild($titleType);
			$otherTitles->appendChild($otherTitle);

			$documentNode->documentElement->appendChild($otherTitles);
		}

		return $documentNode;
	}

	function createAvailability($documentNode)
	{

		$availability = $documentNode->createElement("availability");
		$availabilityType = $documentNode->createElement("availabilityType", "Download");
		$availability->appendChild($availabilityType);
		$documentNode->documentElement->appendChild($availability);

		return $documentNode;

	}

	function createPublicationPlace($documentNode)
	{

		$request = Application::getRequest();
		$press = $request->getPress();
		$location = $press->getData('location');
		$publicationPlace = $documentNode->createElement("publicationPlace", $location);
		$documentNode->documentElement->appendChild($publicationPlace);

		return $documentNode;

	}

	function createDataURLs($documentNode, $object, $parent, $isSubmission)
	{

		$request = Application::getRequest();
		$press = $request->getPress();
		$urlPart = ($isSubmission == true) ? array($object->getId()) : array($parent->getId(), 'c' . $object->getId());

		$testUrl = $this->getPlugin()->getSetting($press->getId(), "testUrl");

		$host = ($this->getPlugin()->isTestMode($press)) ? $testUrl : Request::url($press->getPath());

		$dataURLPath = implode("/", array($host, 'catalog', 'book', $object->getId()));

		$dataURLs = $documentNode->createElement("dataURLs");
		$dataURL = $documentNode->createElement("dataURL", $dataURLPath);
		$dataURLs->appendChild($dataURL);
		$documentNode->documentElement->appendChild($dataURLs);

		return $documentNode;

	}

	function createDoiProposal($documentNode, $object)
	{

		$request = Application::getRequest();
		$press = $request->getPress();
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this->getPlugin()->getSetting($press->getId(), "testPrefix"), $pubId);
			}
			$doiProposal = $documentNode->createElement("doiProposal", $pubId);
			$documentNode->documentElement->appendChild($doiProposal);


		}

		return $documentNode;
	}

	function createRelationsOfChildren($documentNode, $object)
	{

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
					$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this - $this->getPlugin()->getDataciteAPITestPrefix(), $pubId);
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

	function createRelationsOfParent($documentNode, $parent)
	{

		$request = Application::getRequest();
		$press = $request->getPress();
		$relations = $documentNode->createElement("relations");
		$pubId = $parent->getStoredPubId('doi');
		if (isset($pubId)) {

			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this->getPlugin()->getDataciteAPITestPrefix(), $pubId);
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

	function getContext()
	{

		return $this->_context;
	}

	function setContext($context)
	{

		$this->_context = $context;
	}


}
