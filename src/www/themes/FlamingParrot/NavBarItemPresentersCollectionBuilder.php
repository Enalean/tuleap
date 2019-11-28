<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

class FlamingParrot_NavBarItemPresentersCollectionBuilder
{

    private static $NO_ID      = false;
    private static $NOT_ACTIVE = false;

    /** @var PFUser */
    private $user;

    /** @var string */
    private $request_uri;

    /** @var string */
    private $selected_top_tab;

    /** @var array */
    private $extra_tabs;

    /** @var array */
    private $projects;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $registration_user_permission_checker;

    public function __construct(
        PFUser $user,
        $request_uri,
        $selected_top_tab,
        array $extra_tabs,
        array $projects,
        ProjectRegistrationUserPermissionChecker $registration_user_permission_checker
    ) {
        $this->user             = $user;
        $this->request_uri      = $request_uri;
        $this->selected_top_tab = $selected_top_tab;
        $this->extra_tabs       = $extra_tabs;
        $this->projects         = $projects;
        $this->registration_user_permission_checker = $registration_user_permission_checker;
    }

    public function buildNavBarItemPresentersCollection()
    {
        $collection = new FlamingParrot_NavBarItemPresentersCollection();

        $this->addProjectsItem($collection);
        $this->addHelpItem($collection);
        $this->addMoarItem($collection);
        $this->addAdminItem($collection);

        EventManager::instance()->processEvent(
            Event::NAVBAR_ITEMS,
            array(
                'items'            => $collection,
                'selected_top_tab' => $this->selected_top_tab,
                'request_uri'      => $this->request_uri
            )
        );

        return $collection;
    }

    private function addAdminItem(FlamingParrot_NavBarItemPresentersCollection $collection)
    {
        if ($this->user->isSuperUser()) {
            $collection->addItem(new FlamingParrot_NavBarItemAdminPresenter(
                self::$NO_ID,
                $this->isNavBarItemActive('/admin/', 'admin'),
                '/admin/',
                $GLOBALS['Language']->getText('menu', 'administration')
            ));
        }
    }

    private function addProjectsItem(FlamingParrot_NavBarItemPresentersCollection $collection)
    {
        $collection->addItem(new FlamingParrot_NavBarItemProjectsPresenter(
            'project',
            $this->isNavBarItemActive(array('/softwaremap/', '/projects/', '/project/')),
            $this->registration_user_permission_checker->isUserAllowedToCreateProjects($this->user),
            $this->user,
            $this->projects
        ));
    }

    private function addMoarItem(FlamingParrot_NavBarItemPresentersCollection $collection)
    {
        $items = array();
        $links = array();
        foreach ($this->extra_tabs as $tab) {
            $items[] = new FlamingParrot_NavBarItemLinkPresenter(
                self::$NO_ID,
                self::$NOT_ACTIVE,
                $tab['link'],
                $tab['title']
            );
            $links[] = $tab['link'];
        }

        if (count($links) > 0) {
            $item = new FlamingParrot_NavBarItemDropdownPresenter(
                'extra-tabs',
                $this->isNavBarItemActive($links),
                $GLOBALS['Language']->getText('include_menu', 'extras')
            );

            $item->addSection(
                new FlamingParrot_NavBarItemDropdownSectionPresenter($items)
            );

            $collection->addItem($item);
        }
    }

    private function addHelpItem(FlamingParrot_NavBarItemPresentersCollection $collection)
    {
        $item = new FlamingParrot_NavBarItemDropdownPresenter(
            'help',
            $this->isNavBarItemActive(array('/help/', '/contact.php', '/help/api.php'), 'help'),
            $GLOBALS['Language']->getText('include_menu', 'help')
        );

        $item->addSection(
            new FlamingParrot_NavBarItemDropdownSectionPresenter(
                array(
                    new FlamingParrot_NavBarItemLinkPresenter(
                        self::$NO_ID,
                        self::$NOT_ACTIVE,
                        '/help/',
                        $GLOBALS['Language']->getText('include_menu', 'get_help')
                    ),
                    new FlamingParrot_NavBarItemLinkPresenter(
                        self::$NO_ID,
                        self::$NOT_ACTIVE,
                        '/help/api.php',
                        $GLOBALS['Language']->getText('include_menu', 'api')
                    ),
                )
            )
        );
        $item->addSection(
            new FlamingParrot_NavBarItemDropdownSectionPresenter(
                array(
                    new FlamingParrot_NavBarItemLinkPresenter(
                        self::$NO_ID,
                        self::$NOT_ACTIVE,
                        '/contact.php',
                        $GLOBALS['Language']->getText('include_menu', 'contact_us')
                    )
                )
            )
        );

        $collection->addItem($item);
    }

    private function isNavBarItemActive($paths_to_detect, $toptab = null)
    {
        if ($toptab === $this->selected_top_tab) {
            return true;
        }

        if (! is_array($paths_to_detect)) {
            $paths_to_detect = array($paths_to_detect);
        }

        foreach ($paths_to_detect as $path) {
            if (strpos($this->request_uri, $path) === 0) {
                return true;
            }
        }

        return false;
    }
}
