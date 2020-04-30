<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\CLI\Events;

use BackendLogger;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\Event\Dispatchable;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\layout\HomePage\NewsCollectionBuilder;
use Tuleap\layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\ProjectRegistrationPresenterBuilder;
use Tuleap\User\UserSuspensionManager;
use Tuleap\System\ServiceControl;
use Tuleap\Widget\MyProjects;

class GetWhitelistedKeys implements Dispatchable
{
    public const NAME = 'getWhitelistedKeys';

    /**
     * @var array<string, bool>
     */
    private $white_listed_keys = [
        \ProjectManager::CONFIG_PROJECT_APPROVAL => true,
        \ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER => true,
        \ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION => true,
        \ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS => true,
        \ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY => true,
        \ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT => true,
        \ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE => true,
        ProjectVisibilityConfigManager::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY => true,
        Prometheus::CONFIG_PROMETHEUS_PLATFORM => true,
        Prometheus::CONFIG_PROMETHEUS_NODE_EXPORTER => true,
        NewsCollectionBuilder::CONFIG_DISPLAY_NEWS => true,
        StatisticsCollectionBuilder::CONFIG_DISPLAY_STATISTICS => true,
        DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME => true,
        ServiceControl::FORGECONFIG_INIT_MODE => true,
        UserSuspensionManager::CONFIG_NOTIFICATION_DELAY => true,
        MyProjects::CONFIG_DISABLE_CONTACT => true,
        ProjectRegistrationPresenterBuilder::FORGECONFIG_CAN_USE_DEFAULT_SITE_TEMPLATE => true,
        BackendLogger::CONFIG_LOGGER => true,
        UserSuspensionManager::CONFIG_INACTIVE_EMAIL => true
    ];

    public function addPluginsKeys(string $key_name): void
    {
        $this->white_listed_keys[$key_name] = true;
    }

    /**
     * @return string[]
     */
    public function getWhiteListedKeys(): array
    {
        return array_keys($this->white_listed_keys);
    }

    public function isKeyWhiteListed(string $key): bool
    {
        return isset($this->white_listed_keys[$key]);
    }
}
