<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Admin\ProjectCreationNavBarPresenter;

class FieldsListPresenter
{
    public const TEMPLATE = 'description_fields_list';

    public $title;

    /** @var array */
    public $description_fields;

    public $description_fields_header;
    public $btn_add_field;
    public $name_header;
    public $description_header;
    public $require_header;
    public $type_header;
    public $rank_header;
    public $rank_tooltip;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(
        ProjectCreationNavBarPresenter $navbar,
        $title,
        array $description_fields,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->navbar             = $navbar;
        $this->title              = $title;
        $this->description_fields = $description_fields;

        $this->description_fields_header                = _('Project fields');
        $this->btn_add_field                            = $GLOBALS['Language']->getText('admin_desc_fields', 'header_add');
        $this->name_header                              = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_name');
        $this->description_header                       = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_description');
        $this->require_header                           = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_required');
        $this->type_header                              = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_type');
        $this->rank_header                              = $GLOBALS['Language']->getText('admin_desc_fields', 'rank');
        $this->rank_tooltip                             = $GLOBALS['Language']->getText('admin_desc_fields', 'rank_on_screen');

        $this->modal_field_form_name_label              = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_name_label');
        $this->modal_field_form_name_placeholder        = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_name_placeholder');
        $this->modal_field_form_rank_label              = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_rank_label');
        $this->modal_field_form_required_label          = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_required_label');
        $this->modal_field_form_type_label              = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_type_label');
        $this->modal_field_form_type_label_line         = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_type_label_line');
        $this->modal_field_form_type_label_text         = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_type_label_text');
        $this->modal_field_form_description_label       = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_description_label');
        $this->modal_field_form_description_placeholder = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_description_placeholder');
        $this->modal_field_form_description_info        = $GLOBALS['Language']->getText('admin_desc_fields', 'modal_field_form_description_info');

        $this->add_modal_title                          = $GLOBALS['Language']->getText('admin_desc_fields', 'add_modal_title');
        $this->add_modal_cancel                         = $GLOBALS['Language']->getText('admin_desc_fields', 'add_modal_cancel');
        $this->add_modal_submit                         = $GLOBALS['Language']->getText('admin_desc_fields', 'add_modal_submit');

        $this->edit_modal_title                         = $GLOBALS['Language']->getText('admin_desc_fields', 'edit_modal_title');
        $this->edit_modal_cancel                        = $GLOBALS['Language']->getText('admin_desc_fields', 'edit_modal_cancel');
        $this->edit_modal_submit                        = $GLOBALS['Language']->getText('admin_desc_fields', 'edit_modal_submit');

        $this->delete_modal_title                       = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_modal_title');
        $this->delete_modal_content                     = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_modal_content');
        $this->delete_modal_cancel                      = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_modal_cancel');
        $this->delete_modal_submit                      = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_modal_submit');

        $this->csrf_token = $csrf_token;
    }
}
