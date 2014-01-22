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
class Tracker_Artifact_Presenter_EditArtifactInPlacePresenter {

    public $follow_ups;

    public $artifact_links;

    public $submit_url;

    public $form_elements;

    /** @var Tracker_Artifact */
    private $artifact;

    public $javascript_files;


    public function __construct(
        $follow_ups,
        $artifact_links,
        $submit_url,
        $form_elements,
        $artifact,
        $javascript_files
    ) {
        $this->follow_ups       = $follow_ups;
        $this->artifact_links   = $artifact_links;
        $this->submit_url       = $submit_url;
        $this->form_elements    = $form_elements;
        $this->artifact         = $artifact;
        $this->javascript_files = $javascript_files;
    }

    public function artifact_id() {
        return $this->artifact->getId();
    }

    public function last_changeset_id() {
        return $this->artifact->getLastChangeset()->getId();
    }

    public function javascript_rules() {
        return $this->artifact->getTracker()->displayRulesAsJavascript();
    }
}
?>
