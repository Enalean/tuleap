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
class Tracker_Artifact_Presenter_CreateArtifactInPlacePresenter {

    /** @var Tracker */
    private $tracker;

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    public function tracker_title() {
        return $this->tracker->getName();
    }

    public function form_elements() {
       return $this->tracker->fetchSubmitNoColumns();
    }

    public function submit() {
        return $GLOBALS['Language']->getText('plugin_tracker_modal_artifact', 'submit');
    }

    public function cancel() {
        return $GLOBALS['Language']->getText('plugin_tracker_modal_artifact', 'cancel');
    }
}
?>
