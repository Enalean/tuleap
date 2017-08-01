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

namespace Tuleap;

use Admin_Homepage_Dao;
use Event;
use EventManager;
use ForgeConfig;
use HTTPRequest;
use PFUser;

class BurningParrotCompatiblePageDetector
{
    /**
     * @var Admin_Homepage_Dao
     */
    private $homepage_dao;

    public function __construct(Admin_Homepage_Dao $homepage_dao)
    {
        $this->homepage_dao = $homepage_dao;
    }

    public function isInCompatiblePage(PFUser $current_user)
    {
        if (IS_SCRIPT) {
            return false;
        }

        return $this->isInSiteAdmin($current_user)
            || $this->isInDashboard()
            || $this->isInHomepage()
            || $this->isInContact()
            || $this->isInHelp()
            || $this->isInBurningParrotCompatiblePage();
    }

    public function isInSiteAdmin(PFUser $current_user)
    {
        $is_in_site_admin = false;
        EventManager::instance()->processEvent(
            Event::IS_IN_SITEADMIN,
            array(
                'is_in_siteadmin' => &$is_in_site_admin
            )
        );

        $uri = $_SERVER['REQUEST_URI'];

        $is_in_site_admin = $is_in_site_admin ||
            (
                (
                    strpos($uri, '/admin/') === 0 ||
                    strpos($uri, '/tracker/admin/restore.php') === 0
                ) &&
                strpos($uri, '/admin/register_admin.php') !== 0
            );

        return $current_user->isSuperUser() && $is_in_site_admin;
    }

    private function isInDashboard()
    {
        return $this->isInPersonalDasboard() || $this->isInProjectDashboard();
    }

    public function isInHomepage()
    {
        return $_SERVER['REQUEST_URI'] === '/'
            && $this->homepage_dao->isStandardHomepageUsed();
    }

    private function isInContact()
    {
        return strpos($_SERVER['REQUEST_URI'], '/contact.php') === 0;
    }

    private function isInHelp()
    {
        return strpos($_SERVER['REQUEST_URI'], '/help/') === 0;
    }

    private function isInPersonalDasboard()
    {
        $is_managing_bookmarks = strpos($_SERVER['REQUEST_URI'], '/my/bookmark') === 0;

        return ! $is_managing_bookmarks && strpos($_SERVER['REQUEST_URI'], '/my/') === 0;
    }

    private function isInProjectDashboard()
    {
        return strpos($_SERVER['REQUEST_URI'], '/projects/') === 0;
    }

    private function isInBurningParrotCompatiblePage()
    {
        $burning_parrot_compatible_event = new BurningParrotCompatiblePageEvent();
        EventManager::instance()->processEvent($burning_parrot_compatible_event);

        return $burning_parrot_compatible_event->isInBurningParrotCompatiblePage();
    }
}
