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

abstract class Tracker_Artifact_Followup_Item
{

    abstract public function getId();

    abstract public function getFollowUpDate();

    abstract public function getFollowUpClassnames($diff_to_previous);

    abstract public function fetchFollowUp($diff_to_previous);

    abstract public function getHTMLAvatar();

    abstract public function getSubmitterUrl();

    abstract public function getFollowupContent($diff_to_previous);

    /**
     * Return diff between this followup and previous one (HTML code)
     *
     * @return string html
     */
    abstract public function diffToPrevious(
        $format = 'html',
        $user = null,
        $ignore_perms = false,
        $for_mail = false,
        $for_modal = false
    );

    public function diffToPreviousArtifactView(PFUser $user, Tracker_Artifact_Followup_Item $previous_item)
    {
        return $this->diffToPrevious();
    }

    abstract public function getValue(Tracker_FormElement_Field $field);

    abstract public function canHoldValue();

    public function getAvatar()
    {
        return '<div class="tracker_artifact_followup_avatar">' . $this->getHTMLAvatar() . '</div>';
    }

    public function getPermalink()
    {
        $html  = '<a class="tracker_artifact_followup_permalink" href="#followup_' . $this->getId() . '">';
        $html .= '<i class="fa fa-link" title="Link to this followup - #' . $this->getId() . '"></i> ';
        $html .= '</a>';

        return $html;
    }

    public function getUserLink()
    {
        return '<span class="tracker_artifact_followup_title_user">' . $this->getSubmitterUrl() . '</span>';
    }

    public function getTimeAgo()
    {
        return DateHelper::timeAgoInWords($this->getFollowUpDate(), false, true);
    }
}
