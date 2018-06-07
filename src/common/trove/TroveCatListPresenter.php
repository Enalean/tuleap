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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Trove;

use CSRFSynchronizerToken;

class TroveCatListPresenter
{
    /**
     * @var array
     */
    public $trovecats;
    public $delete_button;
    public $cancel;
    public $add;
    public $title;
    public $header_description;
    public $header_name;
    public $edit_trove_cat;

    public $description_placeholder;
    public $root_label;
    public $parent_category_label;
    public $label_description;
    public $label_shortname;

    public $delete_trove_cat;
    public $alert_description_delete_modal;
    public $alert_description_delete_modal_next;
    public $navbar;


    public function __construct($navbar, array $trovecats, CSRFSynchronizerToken $csrf_token)
    {
        $this->title                    = $GLOBALS['Language']->getText('admin_trove_cat_list', 'title');
        $this->header_name              = $GLOBALS['Language']->getText('admin_trove_cat_list', 'header_name');
        $this->header_description       = $GLOBALS['Language']->getText('admin_trove_cat_list', 'header_description');
        $this->edit_button              = $GLOBALS['Language']->getText('admin_trove_cat_list', 'edit');
        $this->delete_button            = $GLOBALS['Language']->getText('admin_trove_cat_list', 'delete');

        $this->edit_trove_cat        = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'header');
        $this->edit                  = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'edit');
        $this->cancel                = $GLOBALS['Language']->getText('admin_trove_cat_list', 'cancel');
        $this->label_fullname        = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'label_fullname');
        $this->label_shortname       = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'label_shortname');
        $this->label_mandatory       = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'label_mandatory');
        $this->mandatory_info        = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'mandatory_info');
        $this->label_display         = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'label_display');
        $this->display_info          = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'display_info');
        $this->label_description     = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'label_description');
        $this->parent_category_label = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'parent_category_label');
        $this->root_label            = $GLOBALS['Language']->getText('admin_trove_cat_edit', 'root');
        $this->add_trove_cat         = $GLOBALS['Language']->getText('admin_trove_cat_add', 'add_trove_cat');
        $this->add                   = $GLOBALS['Language']->getText('admin_trove_cat_add', 'add');

        $this->description_placeholder = $GLOBALS['Language']->getText(
            'admin_trove_cat_edit',
            'description_placeholder'
        );

        $this->delete_trove_cat = $GLOBALS['Language']->getText(
            'admin_trove_cat_delete',
            'delete_trove_cat'
        );

        $this->trovecats  = $trovecats;
        $this->csrf_token = $csrf_token;
        $this->navbar = $navbar;
    }
}
