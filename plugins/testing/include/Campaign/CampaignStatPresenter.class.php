<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class Testing_Campaign_CampaignStatPresenter {

    /** @var int */
    public $nb_not_run;

    /** @var int */
    public $nb_pass;

    /** @var int */
    public $nb_fail;

    /** @var int */
    public $nb_not_completed;

    /** @var int */
    public $percent_complete;

    public function __construct($nb_not_run, $nb_pass, $nb_fail, $nb_not_completed) {
        $this->nb_not_run       = $nb_not_run;
        $this->nb_pass          = $nb_pass;
        $this->nb_fail          = $nb_fail;
        $this->nb_not_completed = $nb_not_completed;
        $total  = $nb_not_run + $nb_pass + $nb_fail + $nb_not_completed;
        $nb_run = $total - $nb_not_run;
        $this->percent_complete      = $total ? floor($nb_run * 100 / $total) : 0;
        $this->percent_not_run       = $total ? floor($nb_not_run * 100 / $total) : 0;
        $this->percent_fail          = $total ? floor($nb_fail * 100 / $total) : 0;
        $this->percent_pass          = $total ? floor($nb_pass * 100 / $total) : 0;
        $this->percent_not_completed = $total ? floor($nb_not_completed * 100 / $total) : 0;
    }
}
