<?php

import('lib.pkp.controllers.list.submissions.SelectSubmissionsListHandler');

class DataciteQueuedListHandler extends SelectSubmissionsListHandler {
	public function init( $args = array() ) {
		$this->_inputName = isset($args['inputName']) ? $args['inputName'] : $this->_inputName;
	}

	public function getItems() {
		$request = Application::getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;

		$submissionService = ServicesContainer::instance()->get('submission');
		$submissions = $submissionService->getSubmissions($context->getId(), $this->_getItemsParams());
		$items = array();
		if (!empty($submissions)) {
			$propertyArgs = array(
				'request' => $request,
			);
			foreach ($submissions as $submission) {
				if (!$submission->getData('pub-id::publisher-id')) {
					$items[] = $submissionService->getBackendListProperties($submission, $propertyArgs);
				}
			}
		}

		return $items;
	}

}
