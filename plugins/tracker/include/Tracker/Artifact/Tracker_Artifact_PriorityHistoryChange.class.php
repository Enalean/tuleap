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

class Tracker_Artifact_PriorityHistoryChange extends Tracker_Artifact_Followup_Item
{

    public const NO_CONTEXT = '-1';

    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var int
     */
    private $id;

    /**
     * @var Tracker_Artifact
     */
    private $moved_artifact;

    /**
     * @var Tracker_Artifact
     */
    private $artifact_higher;

    /**
     * @var Tracker_Artifact
     */
    private $artifact_lower;

    /**
     * @var int
     */
    private $context;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var bool
     */
    private $has_been_raised;

    /**
     * @var PFUser
     */
    private $prioritized_by;

    /**
     * @var int
     */
    private $prioritized_on;


    public function __construct(
        Tracker_ArtifactFactory $tracker_artifact_factory,
        $id,
        Tracker_Artifact $moved_artifact,
        Tracker_Artifact $artifact_higher,
        Tracker_Artifact $artifact_lower,
        $context,
        Project $project,
        $has_been_raised,
        PFUser $prioritized_by,
        $prioritized_on
    ) {
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->id                       = $id;
        $this->moved_artifact           = $moved_artifact;
        $this->artifact_higher          = $artifact_higher;
        $this->artifact_lower           = $artifact_lower;
        $this->context                  = $context;
        $this->project                  = $project;
        $this->has_been_raised          = $has_been_raised;
        $this->prioritized_by           = $prioritized_by;
        $this->prioritized_on           = $prioritized_on;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMovedArtifact()
    {
        return $this->moved_artifact;
    }

    public function getArtifactHigher()
    {
        return $this->artifact_higher;
    }

    public function getArtifactLower()
    {
        return $this->artifact_lower;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function hasBeenRaised()
    {
        return $this->has_been_raised;
    }

    public function getPrioritizedBy()
    {
        return $this->prioritized_by;
    }

    public function getPrioritizedOn()
    {
        return $this->prioritized_on;
    }

    public function getFollowUpClassnames($diff_to_previous)
    {
        return 'tracker_artifact_followup-priority';
    }

    public function getSubmitterUrl()
    {
        $user_helper   = UserHelper::instance();
        $submitter_url = $user_helper->getLinkOnUser($this->prioritized_by);

        return $submitter_url;
    }

    public function getFollowUpDate()
    {
        return $this->prioritized_on;
    }

    public function getHTMLAvatar()
    {
        return $this->prioritized_by->fetchHtmlAvatar();
    }

    public function fetchFollowUp($diff_to_previous)
    {
        $html  = '';
        $html .= $this->getAvatar();

        $html .= '<div class="tracker_artifact_followup_header">';
        $html .= $this->getPermalink();
        $html .= $this->getUserLink();
        $html .= $this->getTimeAgo();
        $html .= '</div>';

        $html .= '<div class="tracker_artifact_followup_content">';
        $html .= $this->getFollowupContent($diff_to_previous);
        $html .= '</div>';

        $html .= '<div style="clear:both;"></div>';

        return $html;
    }

    public function getFollowupContent($diff_to_previous)
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_intro') .
            ' ' . $this->getRankProgression() .
            $this->getContextRepresentation() .
            ' ' . $this->getRelativeArtifactRepresentation();
    }

    private function getRankProgression()
    {
        $html = '<span class="rank-progression">';

        if ($this->hasBeenRaised()) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_raised') . ' &#8599;';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_decreased') . ' &#8600;';
        }

        $html .= '</span>';

        return $html;
    }

    private function getContextRepresentation()
    {
            $html = '';

        if (! is_null($this->context) && $this->context !== self::NO_CONTEXT) {
            $html .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_in') . ' ';

            if ($this->context === '0') {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_backlog');
            } else {
                $artifact = $this->tracker_artifact_factory->getArtifactById($this->context);
                if ($artifact) {
                    $html .= $artifact->fetchColoredXRef();
                }
            }

            $html .= ' ';
        }

        return $html;
    }

    private function getRelativeArtifactRepresentation()
    {
        if ($this->moved_artifact == $this->artifact_higher) {
            return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_before_than') . ' ' . $this->artifact_lower->fetchColoredXRef();
        }

        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'priority_change_after_than') . ' ' . $this->artifact_higher->fetchColoredXRef();
    }

    /**
     * Return diff between this followup and previous one (HTML code)
     *
     * @return string html
     */
    public function diffToPrevious(
        $format = 'html',
        $user = null,
        $ignore_perms = false,
        $for_mail = false,
        $for_modal = false
    ) {
        return '';
    }

    public function getValue(Tracker_FormElement_Field $field)
    {
        return null;
    }

    public function canHoldValue()
    {
        return false;
    }
}
