<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
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

class Tracker_Artifact_Renderer_CreateInPlaceRenderer
{

    /** @var Tracker */
    private $tracker;

    /** @var MustacheRenderer */
    private $renderer;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    public function __construct(Tracker $tracker, MustacheRenderer $renderer)
    {
        $this->tracker                  = $tracker;
        $this->renderer                 = $renderer;
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();
    }

    public function display($artifact_link_id, $render_with_javascript)
    {
        $artifact_to_link = null;
        $submitted_values = array();

        if ($artifact_link_id) {
            $artifact_to_link = $this->tracker_artifact_factory->getArtifactByid($artifact_link_id);
        }

        if ($artifact_to_link) {
            if ($this->isArtifactInParentTracker($artifact_to_link)) {
                $submitted_values['disable_artifact_link_field'] = true;
            } else {
                $submitted_values['disable_artifact_link_field'] = false;
            }
        }

        $submitted_values['render_with_javascript'] = $render_with_javascript;

        $form_elements = $this->tracker->fetchSubmitNoColumns($artifact_to_link, $submitted_values);

        $presenter = new Tracker_Artifact_Presenter_CreateArtifactInPlacePresenter($this->tracker, $artifact_to_link, $form_elements, $render_with_javascript);
        $this->renderer->renderToPage('create-artifact-modal', $presenter);
    }

    private function isArtifactInParentTracker(Tracker_Artifact $linked_artifact)
    {
        $linked_artifact_tracker_id = $linked_artifact->getTrackerId();
        $parent_tracker             = $this->tracker->getParent();

        if ($parent_tracker) {
            return $parent_tracker->getId() == $linked_artifact_tracker_id;
        }

        return false;
    }
}
