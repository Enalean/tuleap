<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class Tracker_GeneralSettings_Presenter {

    /** @var Tracker */
    private $tracker;

    public $action_url;

    /** @var Tracker_ColorPresenterCollection */
    private $color_presenter_collection;

    public function __construct(
        Tracker $tracker,
        $action_url,
        Tracker_ColorPresenterCollection $color_presenter_collection
    ) {
        $this->tracker                    = $tracker;
        $this->action_url                 = $action_url;
        $this->color_presenter_collection = $color_presenter_collection;
    }

    public function colors() {
        return $this->color_presenter_collection;
    }

    public function html_tags() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type','html_tags');
    }

    public function tracker_name() {
        return $this->tracker->getName();
    }

    public function tracker_shortname() {
        return $this->tracker->getItemName();
    }

    public function tracker_description() {
        return $this->tracker->getDescription();
    }

    public function tracker_name_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact','name');
    }

    public function tracker_description_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact','desc');
    }

    public function tracker_shortname_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type','short_name');
    }

    public function tracker_instantiate_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type','instantiate');
    }

    public function is_instatiate_for_new_projects() {
        return $this->tracker->instantiate_for_new_projects;
    }

    public function tracker_color_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact','color');
    }

    public function tracker_color() {
        return $this->tracker->getColor();
    }

    public function preview_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact','preview');
    }

    public function submit_instruction_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type','submit_instr');
    }

    public function submit_insrtuctions() {
        return $this->tracker->submit_instructions;
    }

    public function browse_instruction_label() {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type','browse_instr');
    }

    public function browse_instruction() {
        return $this->tracker->browse_instructions;
    }

    public function submit_button() {
        return $GLOBALS['Language']->getText('global','btn_submit');
    }
}