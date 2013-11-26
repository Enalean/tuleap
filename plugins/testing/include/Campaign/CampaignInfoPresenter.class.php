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

class Testing_Campaign_CampaignInfoPresenter {

    /** @var string */
    public $name;

    /** @var Testing_Campaign_CampaignStatPresenter */
    public $stat;

    /** @var Testing_Release_ReleaseInfoPresenter */
    public $release;

    /** @var Testing_Release_ReleaseInfoPresenter */
    public $cycle;

    public function __construct(
        Testing_Campaign_Campaign $campaign,
        Testing_Release_ReleaseInfoPresenter $release,
        Testing_Release_ReleaseInfoPresenter $cycle,
        Testing_Campaign_CampaignStatPresenter $stat
    ) {
        $this->name       = $campaign->getName();
        $this->stat       = $stat;
        $this->release    = $release;
        $this->cycle      = $cycle;
        $this->show_uri   = '/plugins/testing/?group_id='. $campaign->getProjectId() .'&resource=campaign&action=show&id='. $campaign->getId();
        $this->edit_uri   = '/plugins/testing/?group_id='. $campaign->getProjectId() .'&resource=campaign&action=edit&id='. $campaign->getId();
        $this->delete_uri = '/plugins/testing/?group_id='. $campaign->getProjectId() .'&resource=campaign&action=delete&id='. $campaign->getId();
    }
}
