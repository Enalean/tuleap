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
    public function __construct(array $followups)
    {
        $this->followups = $this->buildFollowUpsPresenters($followups);
    }

    public function no_comment()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_modal_artifact', 'no_comment');
    }

    /**
     * @param Tracker_Artifact_Followup_Item[] $followups
     * @return array
     */
    private function buildFollowUpsPresenters(array $followups)
    {
        $presenters = array();
        foreach ($followups as $followup) {
            $diff_to_previous = $followup->diffToPrevious();
            $presenters[] = array(
                'getId'              => $followup->getId(),
                'getAvatar'          => $followup->getAvatar(),
                'getUserLink'        => $followup->getUserLink(),
                'getTimeAgo'         => $followup->getTimeAgo(),
                'getFollowupContent' => $followup->getFollowupContent($diff_to_previous)
            );
        }

        return $presenters;
    }
}
