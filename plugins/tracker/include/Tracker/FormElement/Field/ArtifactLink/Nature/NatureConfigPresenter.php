<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Tuleap\Tracker\Config\SectionsPresenter;
use CSRFSynchronizerToken;

class NatureConfigPresenter {

    public $csrf_token;
    public $desc;
    public $title;
    public $shortname_label;
    public $forward_label_label;
    public $reverse_label_label;
    public $btn_submit;
    public $btn_close;
    public $shortname_help;
    public $forward_label_help;
    public $reverse_label_help;
    public $shortname_placeholder;
    public $forward_label_placeholder;
    public $reverse_label_placeholder;
    public $create_new_nature;
    public $edit_nature;
    public $shortname_pattern;
    public $natures_usage;
    public $has_natures;
    public $sections;
    public $available_natures;
    public $allowed_projects;
    public $allowed_projects_title;
    public $edit_icon_label;
    public $edit_system_nature_title;
    public $delete_modal_title;
    public $delete_modal_submit;
    public $delete_modal_content;

    public function __construct($title, array $natures_usage, CSRFSynchronizerToken $csrf, $allowed_projects) {
        $this->desc                = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'desc');
        $this->available_natures   = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'available_natures');
        $this->shortname_label     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'shortname');
        $this->forward_label_label = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'forward_label');
        $this->reverse_label_label = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'reverse_label');
        $this->btn_submit          = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'update_button');
        $this->btn_close           = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'cancel_modal');

        $this->shortname_help     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'shortname_help');
        $this->forward_label_help = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'forward_label_help');
        $this->reverse_label_help = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'reverse_label_help');

        $this->allowed_projects_title    = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'list_of_allowed_projects');
        $this->allowed_projects_desc     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'list_of_allowed_projects_desc');
        $this->shortname_placeholder     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'shortname_placeholder');
        $this->forward_label_placeholder = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'forward_label_placeholder');
        $this->reverse_label_placeholder = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'reverse_label_placeholder');

        $this->create_new_nature        = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'create_new_nature');
        $this->edit_nature              = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'edit_nature');
        $this->edit_icon_label          = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'edit_icon_label');
        $this->edit_system_nature_title = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'edit_system_nature_title');
        $this->delete_icon_label        = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_icon_label');
        $this->cannot_delete_title      = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'cannot_delete_title');
        $this->delete_modal_title       = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_modal_title');
        $this->delete_modal_submit      = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_modal_submit');
        $this->delete_modal_content     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_modal_content');
        $this->shortname_pattern        = NatureValidator::SHORTNAME_PATTERN;

        $this->sections = new SectionsPresenter();

        $this->title            = $title;
        $this->natures_usage    = $natures_usage;
        $this->has_natures      = count($this->natures_usage) > 0;
        $this->no_natures       = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'there_is_no_nature');
        $this->csrf_token       = $csrf->fetchHTMLInput();
        $this->allowed_projects = $allowed_projects;
    }
}
