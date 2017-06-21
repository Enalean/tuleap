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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tuleap_Tour_FlamingParrotBurningParrotUnificationTour extends Tuleap_Tour
{
    const TOUR_NAME = 'flaming-parrot-burning-parrot-unification-tour';

    public function __construct()
    {
        $steps = array(
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'new_style_title'),
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'new_style'),
                'bottom',
                '.navbar-inner'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'personal_page_title'),
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'personal_page'),
                'bottom',
                '#navbar-user-navigation'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'account_title'),
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'account'),
                'bottom',
                '#navbar-user-settings'
            ),
            new Tuleap_Tour_Step(
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'be_prepared_title'),
                $GLOBALS['Language']->getText('fp_bp_unification_tour', 'be_prepared')
            )
        );

        parent::__construct(self::TOUR_NAME, $steps);
    }
}
