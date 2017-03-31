<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 */

use Tuleap\Admin\Homepage\StatisticsBadgePresenter;
use Tuleap\Admin\Homepage\StatisticsPresenter;

require_once 'autoload.php';
require_once 'constants.php';

class enalean_licensemanagerPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-enalean_licensemanager', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::GET_SITEADMIN_HOMEPAGE_USER_STATISTICS);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return Tuleap\Enalean\LicenseManager\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Enalean\LicenseManager\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    /** @see Event::GET_SITEADMIN_HOMEPAGE_USER_STATISTICS */
    public function get_siteadmin_homepage_user_statistics(array $params)
    {
        $nb_max_users = $this->getMaxUsers();
        if (! $nb_max_users) {
            return;
        }

        /** @var Tuleap\Admin\Homepage\NbUsersByStatus $users_by_status */
        $users_by_status    = $params['nb_users_by_status'];
        $nb_users_for_quota = $users_by_status->getNbActive()
            + $users_by_status->getNbPending()
            + $users_by_status->getNbRestricted()
            + $users_by_status->getNbAllValidated();

        $nb_alive_users_label = sprintf(
            dngettext(
                'tuleap-enalean_licensemanager',
                '%d user',
                '%d users',
                $nb_users_for_quota
            ),
            $nb_users_for_quota
        );

        $max_allowed_users_label = sprintf(
            dngettext(
                'tuleap-enalean_licensemanager',
                '%d allowed',
                '%d allowed',
                $nb_max_users
            ),
            $nb_max_users
        );

        $params['additional_statistics'][] = new StatisticsPresenter(
            dgettext('tuleap-enalean_licensemanager', 'Allowed users quota'),
            array(
                new StatisticsBadgePresenter(
                    "$nb_alive_users_label / $max_allowed_users_label",
                    StatisticsBadgePresenter::LEVEL_DANGER
                )
            )
        );
    }

    private function getMaxUsers()
    {
        $filename = $this->getPluginEtcRoot() . '/max_users.txt';
        if (! is_file($filename)) {
            return 0;
        }

        $nb_max_users = (int) file_get_contents($filename);

        return $nb_max_users;
    }
}
