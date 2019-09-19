<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class Tuleap_Tour_WelcomeTour extends Tuleap_Tour
{

    public const TOUR_NAME = 'tuleap-welcome-tour';

    public function __construct(PFUser $user)
    {
        $hp        = Codendi_HTMLPurifier::instance();
        $user_name = $hp->purify($user->getRealName());

        $steps = array(
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'welcome_title'),
                $GLOBALS['Language']->getText('my_tour', 'welcome', $user_name)
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'my_personal_page_title'),
                $GLOBALS['Language']->getText('my_tour', 'my_personal_page'),
                'bottom',
                '#navbar-user-navigation'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'user_menu_title'),
                $GLOBALS['Language']->getText('my_tour', 'user_menu'),
                'bottom',
                '.user-menu + li'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'projects_title'),
                $GLOBALS['Language']->getText('my_tour', 'projects'),
                'bottom',
                'ul.nav > li.projects-nav'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'help_title'),
                $GLOBALS['Language']->getText('my_tour', 'help'),
                'bottom',
                'ul.nav > li.help-nav'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'search_title'),
                $GLOBALS['Language']->getText('my_tour', 'search'),
                'bottom',
                'ul.nav.pull-right > form'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('my_tour', 'end_tour_title'),
                $GLOBALS['Language']->getText('my_tour', 'end_tour')
            )
        );

        parent::__construct(self::TOUR_NAME, $steps);
    }
}
