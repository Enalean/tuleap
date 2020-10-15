<?php
/**
 * Copyright (c) Enalean, 2014 - present. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

class Tracker_Artifact_Presenter_EditArtifactInPlacePresenter
{

    public $artifact_title;

    public $artifact_uri;

    public $follow_ups;

    public $artifact_links;

    public $form_elements;

    /** @var Artifact */
    private $artifact;

    public $artifact_id;

    public $last_changeset_id;

    /** @var PFUser */
    private $user;

    /** @var HiddenFieldsetsDetector */
    private $hidden_fieldsets_detector;

    public function __construct(
        $follow_ups,
        $artifact_links,
        $form_elements,
        Artifact $artifact,
        PFUser $user,
        HiddenFieldsetsDetector $hidden_fieldsets_detector
    ) {
        $this->follow_ups                = $follow_ups;
        $this->artifact_links            = $artifact_links;
        $this->artifact                  = $artifact;
        $this->artifact_id               = $artifact->getId();
        $this->artifact_title            = $this->getEmptyStringIfNull($artifact->getTitle());
        $this->artifact_uri              = $artifact->getUri();
        $this->last_changeset_id         = $artifact->getLastChangeset()->getId();
        $this->form_elements             = $form_elements;
        $this->user                      = $user;
        $this->hidden_fieldsets_detector = $hidden_fieldsets_detector;
    }

    public function artifact_links_title()
    {
        return dgettext('tuleap-tracker', 'Artifact links');
    }

    public function artifact_links_readonly()
    {
        return sprintf(dgettext('tuleap-tracker', 'In order to edit artifact links, please <a href="%1$s">switch to the full edit mode</a>.'), $this->artifact_uri);
    }

    public function no_artifact_links()
    {
        return dgettext('tuleap-tracker', 'No artifact links');
    }

    public function add_followup_placeholder()
    {
        return dgettext('tuleap-tracker', 'Add a new comment...');
    }

    public function followups_title()
    {
        return dgettext('tuleap-tracker', 'Follow-ups');
    }

    public function javascript_rules()
    {
        return $this->artifact->getTracker()->displayRulesAsJavascript();
    }

    public function submit()
    {
        return dgettext('tuleap-tracker', 'Submit');
    }

    public function cancel()
    {
        return dgettext('tuleap-tracker', 'Cancel');
    }

    public function user_is_logged_in()
    {
        return $this->user->isLoggedIn();
    }

    /**
     * @return string
     */
    private function getEmptyStringIfNull($value)
    {
        if ($value === null) {
            return '';
        }
        return $value;
    }

    public function parent_artifact_presenter()
    {
        $parent_artifact_presenter = [];
        $parent_artifact           = $this->artifact->getParent($this->user);

        if ($parent_artifact) {
            $parent_artifact_presenter['xref'] = $parent_artifact->getXRef();
            $parent_artifact_presenter['uri']  = $parent_artifact->getUri();
        }

        return $parent_artifact_presenter;
    }

    public function parent_artifact_label()
    {
        return dgettext('tuleap-tracker', 'Parent artifact:');
    }

    public function has_hidden_fieldsets(): bool
    {
        return $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($this->artifact);
    }
}
