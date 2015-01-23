<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

abstract class Tracker_Artifact_Followup_Item {

    abstract public function getId();

    abstract public function getFollowUpDate();

    abstract public function getFollowUpClassnames();

    abstract public function fetchFollowUp();

    public function getAvatarIfEnabled($avatar) {
        if (Config::get('sys_enable_avatars')) {
            return '<div class="tracker_artifact_followup_avatar">' . $avatar . '</div>';
        }

        return '';
    }

    public function getPermalink() {
        $html  = '<a class="tracker_artifact_followup_permalink" href="#followup_' . $this->getId() . '">';
        $html .= '<i class="icon-link" title="Link to this followup - #' . $this->getId() . '"></i> ';
        $html .= '</a>';

        return $html;
    }

    public function getUserLink($url) {
        return '<span class="tracker_artifact_followup_title_user">'. $url .'</span>';
    }

    public function getTimeAgo($date) {
        return DateHelper::timeAgoInWords($date, false, true);
    }

}