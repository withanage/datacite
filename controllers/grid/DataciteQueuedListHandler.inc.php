<?php

import('lib.pkp.controllers.list.submissions.SelectSubmissionsListHandler');

class DataciteQueuedListHandler extends SelectSubmissionsListHandler {

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
				$items[] = $submissionService->getBackendListProperties($submission, $propertyArgs);
			}
		}

		return $items;
	}

	public function init($args = array()) {

		$this->_inputName = isset($args['inputName']) ? $args['inputName'] : $this->_inputName;
	}

}
