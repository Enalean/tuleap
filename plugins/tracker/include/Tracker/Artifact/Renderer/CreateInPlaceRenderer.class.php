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

class Tracker_Artifact_Renderer_CreateInPlaceRenderer{

    /** @var Tracker */
    private $tracker;

    /** @var MustacheRenderer */
    private $renderer;

    public function __construct(Tracker $tracker, MustacheRenderer $renderer) {
        $this->tracker  = $tracker;
        $this->renderer = $renderer;
    }

    public function display() {
        $presenter = new Tracker_Artifact_Presenter_CreateArtifactInPlacePresenter($this->tracker);
        $this->renderer->renderToPage('create-artifact-modal', $presenter);
    }
}