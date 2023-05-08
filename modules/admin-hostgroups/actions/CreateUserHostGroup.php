<?php
/**
  *
  *
  * @version 6.1.0
  * @author Wolfgang Alper <wolfgang.alper@intellitrend.de>
  * @copyright IntelliTrend GmbH, https://www.intellitrend.de
  * @license GNU Lesser General Public License v3.0
  *
  * You can redistribute this library and/or modify it under the terms of
  * the GNU LGPL as published by the Free Software Foundation,
  * either version 3 of the License, or any later version.
  * However you must not change author and copyright information.  
  */

declare(strict_types = 1);

namespace Modules\Iahg\Actions;

use CControllerResponseData;
use CControllerResponseFatal;
use CController as CAction;
use CApiService;
use API;

/**
 * Admin create hostgroups module action.
 */
class CreateUserHostGroup extends CAction {

	/**
	 * Initialize action. Method called by Zabbix core.
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * Disable SID (Session ID) validation. Session ID validation should only be used for actions which involve data
		 * modification, such as update or delete actions. In such case Session ID must be presented in the URL, so that
		 * the URL would expire as soon as the session expired.
		 */
		$this->disableCsrfValidation();
	}

	/**
	 * Check and sanitize user input parameters. Method called by Zabbix core. Execution stops if false is returned.
	 *
	 * @return bool true on success, false on error.
	 */
	protected function checkInput(): bool {
		$fields = [
			'prefix' 	=> 'int32',
			'name' 		=> 'string'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	/**
	 * Check if the user has permission to execute this action. Method called by Zabbix core.
	 * Execution stops if false is returned.
	 *
	 * @return bool
	 */
	protected function checkPermissions(): bool {
		return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
	}

	private function createHostGroup($name, $prefixIndex, $prefixList) {
		if (!isset($prefixList[$prefixIndex])) {
			error(_('Invalid host group prefix'));
			return FALSE;
		}

		$hostgroupNew = $prefixList[$prefixIndex] . '/' . $name;

		$messageSuccess = _('Group added');
		$messageFailed = _('Cannot add group');

		$oldUserType = CApiService::$userData['type'];
		$result = NULL;

		try {
			// HACK: temporarily "promote" the user to super admin to allow creating host groups via API
			CApiService::$userData['type'] = USER_TYPE_SUPER_ADMIN;
			$result = API::HostGroup()->create(['name' => $hostgroupNew]);

			if ($result) {
				uncheckTableRows();
			}
		} finally {
			// make sure the type is always restored correctly!
			CApiService::$userData['type'] = $oldUserType;
		}

		show_messages($result, $messageSuccess, $messageFailed);
		return TRUE;
	}

	/**
	 * Returns an array of available host group names for the current user.
	 * 
	 * @return array of host group names
	 */
	private function getHostGroupNames() {
		$hostgroups = API::HostGroup()->get([
			'output' => ['name'],
			'editable' => true
		]);

		$hostgroupNames = [];
		foreach ($hostgroups as &$hostgroup) {
			$hostgroupNames[] = $hostgroup['name'];
		}

		return $hostgroupNames;
	}

    /**
	 * Prepare the response object for the view. Method called by Zabbix core.
	 *
	 * @return void
	 */
	protected function doAction() {
		$prefixList = $this->getHostGroupNames();
		$prefix = '';
		$name = '';

		// check for host group creation request
		if ($this->hasInput('name') && $this->hasInput('prefix')) {
			$prefix = $this->getInput('prefix');
			$name = $this->getInput('name');
			if ($this->createHostGroup($name, $prefix, $prefixList)) {
				// update host group list so it contains the newly created group
				$prefixList = $this->getHostGroupNames();
			}
		}

        $data = [
			'prefix' 	=> $prefix,
			'name' 		=> $name,
			'groups' 	=> $prefixList
        ];

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Host group edit'));
		$this->setResponse($response);
    }
}
?>