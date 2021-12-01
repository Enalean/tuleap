<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use CSRFSynchronizerToken;
use Project;

class IndexPresenter
{
    /**
     * @var LabelPresenter[]
     */
    public $labels;
    public $title;
    public $name_label;
    public $has_labels;
    public $empty_state;
    public $filter_placeholder;
    public $empty_filter;
    public $is_used_label;
    public $this_label_is_used;
    public $delete_button;
    public $project_id;
    public $csrf_token;
    public $cancel;
    public $color_label;
    public $edit_button;
    public $json_encoded_label_names;
    public $style_label;
    public $plain;
    public $outline;
    public $add_label;
    public $default_label;

    public function __construct(
        $title,
        Project $project,
        CollectionOfLabelPresenter $collection,
        LabelPresenter $default_label,
        CSRFSynchronizerToken $csrf_token,
    ) {
        $this->labels        = $collection->getPresenters();
        $this->title         = $title;
        $this->project_id    = $project->getID();
        $this->has_labels    = count($this->labels) > 0;
        $this->csrf_token    = $csrf_token;
        $this->default_label = $default_label;

        $this->name_label         = _('Name');
        $this->color_label        = _('Color');
        $this->style_label        = _('Style');
        $this->is_used_label      = _('Is used?');
        $this->this_label_is_used = _('This label is used in the project');
        $this->empty_state        = _("No labels defined in this project");
        $this->empty_filter       = _("No matching labels");
        $this->filter_placeholder = _('Name');
        $this->delete_button      = _('Delete');
        $this->edit_button        = _('Edit');
        $this->cancel             = _('Cancel');
        $this->plain              = _('Filled');
        $this->outline            = _('Outline');
        $this->add_label          = _('Add label');
        $this->placeholder        = _('Emergency');

        $this->json_encoded_label_names = json_encode($this->getLabelsNames());
    }

    private function getLabelsNames()
    {
        return array_map(
            function (LabelPresenter $label_presenter) {
                return $label_presenter->name;
            },
            $this->labels
        );
    }
}
