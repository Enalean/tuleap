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
use EventManager;
use HTTPRequest;
use PFUser;
use Tuleap\Request\CurrentPage;

class BurningParrotCompatiblePageDetector
{
    /**
     * @var Admin_Homepage_Dao
     */
    private $homepage_dao;
    /**
     * @var CurrentPage
     */
    private $current_page;

    public function __construct(CurrentPage $current_page, Admin_Homepage_Dao $homepage_dao)
    {
        $this->homepage_dao = $homepage_dao;
        $this->current_page = $current_page;
    }

    public function isInCompatiblePage(PFUser $current_user)
    {
        if (IS_SCRIPT) {
            return false;
        }

        return $this->isInCoreServicesSiteAdmin($current_user)
            || $this->current_page->isDashboard()
            || $this->isInHomepage()
            || $this->isManagingLabels()
            || $this->isInProjectAdmin()
            || $this->isInContact()
            || $this->isInHelp()
            || $this->isInBurningParrotCompatiblePage()
            || $this->isSoftwareMap();
    }

    private function isManagingLabels()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/labels.php') === 0;
    }

    private function isInProjectAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/editgroupinfo.php') === 0
            || strpos($_SERVER['REQUEST_URI'], '/project/admin/ugroup.php') === 0;
    }

    private function isInCoreServicesSiteAdmin(PFUser $current_user)
    {
        $uri = $_SERVER['REQUEST_URI'];

        $is_in_site_admin = (
                    strpos($uri, '/admin/') === 0 ||
                    strpos($uri, '/tracker/admin/restore.php') === 0
                ) &&
                strpos($uri, '/admin/register_admin.php') !== 0;

        return $is_in_site_admin && $current_user->isSuperUser();
    }

    public function isInHomepage()
    {
        return ($_SERVER['REQUEST_URI'] === '/' || strpos($_SERVER['REQUEST_URI'], '/index.php') === 0)
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

    private function isSoftwareMap()
    {
        return strpos($_SERVER['REQUEST_URI'], '/softwaremap/') === 0;
    }

    private function isInBurningParrotCompatiblePage()
    {
        $burning_parrot_compatible_event = new BurningParrotCompatiblePageEvent();
        EventManager::instance()->processEvent($burning_parrot_compatible_event);

        return $burning_parrot_compatible_event->isInBurningParrotCompatiblePage();
    }
}
