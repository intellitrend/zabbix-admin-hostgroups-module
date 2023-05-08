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

namespace Modules\Iahg;

use APP;
use CController as CAction;
use CWebUser;
use Zabbix\Core\CModule;

/**
 * Please see Core\CModule class for additional reference.
 */
class Module extends CModule {

	/**
	 * Initialize module.
	 */
	public function init(): void {
		// guests and non-admins don't need this host group editor
		if (CWebUser::isGuest() || CWebUser::getType() == USER_TYPE_ZABBIX_USER) {
			return;
		}
		// Initialize main menu (CMenu class instance).
		APP::Component()->get('menu.main')->findOrAdd(_('Data collection'))->getSubmenu()->add((new \CMenuItem(_('Host group as admin')))->setAction('iahg.create'));
	}

	/**
	 * Event handler, triggered before executing the action.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onBeforeAction(CAction $action): void {
	}

	/**
	 * Event handler, triggered on application exit.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onTerminate(CAction $action): void {
	}
}