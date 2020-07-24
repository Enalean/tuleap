<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
class Tracker_Artifact_Presenter_FollowUpCommentsPresenter
{

    /** @var PFUser */
    protected $user;

    /** @var array */
    public $followups;

    /**
     * @param Tracker_Artifact_Followup_Item[] $followups
     */
    public function __construct(array $followups, PFUser $current_user)
    {
        $this->followups = $this->buildFollowUpsPresenters($followups, $current_user);
    }

    public function no_comment()
    {
        return dgettext('tuleap-tracker', 'No comment');
    }

    /**
     * @param Tracker_Artifact_Followup_Item[] $followups
     * @return array
     */
    private function buildFollowUpsPresenters(array $followups, PFUser $current_user)
    {
        $presenters = [];
        foreach ($followups as $followup) {
            $diff_to_previous = $followup->diffToPrevious();
            $presenters[] = [
                'getId'              => $followup->getId(),
                'getAvatar'          => $followup->getAvatar(),
                'getUserLink'        => $followup->getUserLink(),
                'getTimeAgo'         => $followup->getTimeAgo($current_user),
                'getFollowupContent' => $followup->getFollowupContent($diff_to_previous, $current_user)
            ];
        }

        return $presenters;
    }
}
