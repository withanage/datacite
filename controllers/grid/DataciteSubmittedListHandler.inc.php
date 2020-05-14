<?php

import('lib.pkp.controllers.list.submissions.SelectSubmissionsListHandler');

class DataciteSubmittedListHandler extends SelectSubmissionsListHandler {

	public function getItems() {

		$request = Application::getRequest();
		$context = $request->getContext();
		$submissionService = ServicesContainer::instance()->get('submission');
		$submissions = $submissionService->getSubmissions($context->getId(), $this->_getItemsParams());
		$items = array();
		if (!empty($submissions)) {
			$propertyArgs = array(
				'request' => $request,
			);
			foreach ($submissions as $submission) {
				if ($submission->getData('pub-id::publisher-id')) {
					$items[] = $submissionService->getBackendListProperties($submission, $propertyArgs);
				}
			}
		}

		return $items;
	}

	public function getItemsMax() {

		$request = Application::getRequest();
		$context = $request->getContext();

		$submissionService = ServicesContainer::instance()->get('submission');
		$submissions = $submissionService->getSubmissions($context->getId(), $this->_getItemsParams());
		$count = 0;
		foreach ($submissions as $submission) {
			if ($submission->getData('pub-id::publisher-id')) {
				$count += 1;
			}
		}

		return $count;
	}

	public function init($args = array()) {

		parent::init($args);
		$this->_inputName = isset($args['inputName']) ? $args['inputName'] : $this->_inputName;
	}

}
