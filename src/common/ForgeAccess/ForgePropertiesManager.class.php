<?php
/**
  * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

use Tuleap\ForgeAccess\UnknownForgeAccessValueException;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\User\UserGroup\NameTranslator;

class ForgeAccess_ForgePropertiesManager
{
    public const POSSIBLE_ACCESS_VALUES = [ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR, ForgeAccess::RESTRICTED];

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var ConfigDao
     */
    private $config_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var PermissionsManager
     */
    private $permission_manager;

    /**
     * @var FRSPermissionCreator
     */
    private $frs_permission_creator;

    public function __construct(
        ConfigDao $config_dao,
        ProjectManager $project_manager,
        PermissionsManager $permission_manager,
        EventManager $event_manager,
        FRSPermissionCreator $frs_permission_creator
    ) {
        $this->config_dao             = $config_dao;
        $this->project_manager        = $project_manager;
        $this->permission_manager     = $permission_manager;
        $this->event_manager          = $event_manager;
        $this->frs_permission_creator = $frs_permission_creator;
    }

    /**
     * @throws UnknownForgeAccessValueException
     */
    public function updateAccess($new_value, $old_value)
    {
        if ($new_value === $old_value) {
            return;
        }

        if (! in_array($new_value, self::POSSIBLE_ACCESS_VALUES, true)) {
            throw new UnknownForgeAccessValueException($new_value, self::POSSIBLE_ACCESS_VALUES);
        }

        $property_name = ForgeAccess::CONFIG;
        $this->config_dao->save($property_name, $new_value);

        $this->event_manager->processEvent(Event::SITE_ACCESS_CHANGE, ['new_value' => $new_value, 'old_value' => $old_value]);

        $this->frs_permission_creator->updateSiteAccess($old_value);

        if ($old_value === ForgeAccess::RESTRICTED || $new_value === ForgeAccess::RESTRICTED) {
            $this->project_manager->disableAllowRestrictedForAll();
            $this->permission_manager->disableRestrictedAccess();
        }
    }

    public function updateLabels($authenticated_label, $registered_label)
    {
        $this->config_dao->save(NameTranslator::CONFIG_AUTHENTICATED_LABEL, $authenticated_label);
        $this->config_dao->save(NameTranslator::CONFIG_REGISTERED_LABEL, $registered_label);
    }

    public function updateAnonymousCanSeeSiteHomePage($new_value)
    {
        return $this->config_dao->save(ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE, $new_value);
    }

    public function updateAnonymousCanSeeContact($new_value)
    {
        return $this->config_dao->save(ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT, $new_value);
    }
}
