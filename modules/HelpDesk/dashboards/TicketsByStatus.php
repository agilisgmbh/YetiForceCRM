<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class HelpDesk_TicketsByStatus_Dashboard extends Vtiger_IndexAjax_View
{

	function getSearchParams($value, $assignedto = '')
	{

		$listSearchParams = array();
		$conditions = array(array('ticketstatus', 'e', $value));
		if ($assignedto != '')
			array_push($conditions, array('assigned_user_id', 'e', getUserFullName($assignedto)));
		$listSearchParams[] = $conditions;
		return '&search_params=' . json_encode($listSearchParams);
	}

	/**
	 * Function returns Tickets grouped by Status
	 * @param type $data
	 * @return <Array>
	 */
	public function getTicketsByStatus($owner)
	{
		$db = PearDatabase::getInstance();
		$module = 'HelpDesk';
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$ownerSql = $moduleModel->getOwnerWhereConditionForDashBoards($owner);
		$ticketStatus = Settings_SupportProcesses_Module_Model::getTicketStatusNotModify();
		$params = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$instance = CRMEntity::getInstance($module);
		$securityParameter = $instance->getUserAccessConditionsQuerySR($module, $currentUser);

		$sql = 'SELECT COUNT(*) as count
					, priority, vtiger_ticketpriorities.color,
					CASE WHEN vtiger_troubletickets.status IS NULL OR vtiger_troubletickets.status = "" THEN "" ELSE vtiger_troubletickets.status END AS statusvalue 
				FROM
					vtiger_troubletickets
				INNER JOIN vtiger_crmentity
					ON vtiger_troubletickets.ticketid = vtiger_crmentity.crmid AND vtiger_crmentity.deleted=0
				INNER JOIN vtiger_ticketstatus
					ON vtiger_troubletickets.status = vtiger_ticketstatus.ticketstatus
				INNER JOIN vtiger_ticketpriorities
					ON vtiger_ticketpriorities.`ticketpriorities` = vtiger_troubletickets.`priority`
				WHERE
					vtiger_crmentity.`deleted` = 0';
		if (!empty($ownerSql)) {
			$sql .= ' AND ' . $ownerSql;
		}
		if (!empty($ticketStatus)) {
			$ticketStatusSearch = implode("','", $ticketStatus);
			$sql .= " AND vtiger_troubletickets.status NOT IN ('$ticketStatusSearch')";
		}
		if ($securityParameter != '')
			$sql .= $securityParameter;

		$sql .= ' GROUP BY 
					statusvalue, priority 
				ORDER BY
				vtiger_ticketstatus.sortorderid';

		$result = $db->query($sql);
		$response = array();
		$priorities = [];
		$status = [];
		$counter = 0;
		$colors = [];
		$numRows = $db->num_rows($result);

		for ($i = 0; $i < $numRows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$tickets[$row['statusvalue']][$row['priority']] = $row['count'];
			if (!array_key_exists($row['priority'], $priorities)) {
				$priorities[$row['priority']] = $counter++;
				$colors[$row['priority']] = $row['color'];
			}
			if (!in_array($row['statusvalue'], $status))
				$status[] = $row['statusvalue'];
		}
		if ($numRows > 0) {
			$counter = 0;
			$result = array();

			foreach ($tickets as $ticketKey => $ticketValue) {
				foreach ($priorities as $priorityKey => $priorityValue) {
					$result[$priorityValue]['data'][$counter][0] = $counter;
					$result[$priorityValue]['label'] = vtranslate($priorityKey, 'HelpDesk');
					$result[$priorityValue]['color'] = $colors[$priorityKey];
					if ($ticketValue[$priorityKey]) {
						$result[$priorityValue]['data'][$counter][1] = $ticketValue[$priorityKey];
					} else {
						$result[$priorityValue]['data'][$counter][1] = 0;
					}
				}
				$counter++;
			}

			$ticks = [];
			foreach ($status as $key => $value) {
				$newArray = [$key, vtranslate($value, 'HelpDesk')];
				array_push($ticks, $newArray);
				$name[] = $value;
			}

			$response['chart'] = $result;
			$response['ticks'] = $ticks;
			$response['name'] = $name;
		}
		return $response;
	}

	public function process(Vtiger_Request $request)
	{
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->get('data');
		$createdTime = $request->get('createdtime');
		$widget = Vtiger_Widget_Model::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = Settings_WidgetsManagement_Module_Model::getDefaultUserId($widget, $moduleName);
		else
			$owner = $request->get('owner');
		if ($owner == 'all')
			$owner = '';

		//Date conversion from user to database format
		if (!empty($createdTime)) {
			$dates['start'] = Vtiger_Date_UIType::getDBInsertedValue($createdTime['start']);
			$dates['end'] = Vtiger_Date_UIType::getDBInsertedValue($createdTime['end']);
		}

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$data = ($owner === false) ? array() : $this->getTicketsByStatus($owner);

		$listViewUrl = $moduleModel->getListViewUrl();
		$statusmount = count($data['name']);
		for ($i = 0; $i < $statusmount; $i++) {
			$data['links'][$i][0] = $i;
			$data['links'][$i][1] = $listViewUrl . $this->getSearchParams($data['name'][$i], $owner);
		}
		//Include special script and css needed for this widget

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);

		$accessibleUsers = $currentUser->getAccessibleUsersForModule($moduleName);
		$accessibleGroups = $currentUser->getAccessibleGroupForModule($moduleName);
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('ACCESSIBLE_GROUPS', $accessibleGroups);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/TicketsByStatus.tpl', $moduleName);
		}
	}
}
