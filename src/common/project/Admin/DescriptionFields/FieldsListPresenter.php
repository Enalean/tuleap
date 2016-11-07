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

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Layout\PaginationPresenter;

class FieldsListPresenter
{
    const TEMPLATE = 'description_fields_list';

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
    public $no_description_fields;

    public function __construct(
        $title,
        array $description_fields
    ) {
        $this->title              = $title;
        $this->description_fields = $description_fields;

        $this->description_fields_header = $GLOBALS['Language']->getText('admin_desc_fields', 'description_fields_header');
        $this->btn_add_field             = $GLOBALS['Language']->getText('admin_desc_fields', 'header_add');
        $this->no_description_fields     = $GLOBALS['Language']->getText('admin_desc_fields', 'no_description_fields');
        $this->name_header               = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_name');
        $this->description_header        = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_description');
        $this->require_header            = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_required');
        $this->type_header               = $GLOBALS['Language']->getText('admin_desc_fields', 'desc_type');
        $this->rank_header               = $GLOBALS['Language']->getText('admin_desc_fields', 'rank');
        $this->rank_tooltip               = $GLOBALS['Language']->getText('admin_desc_fields', 'rank_on_screen');
    }
}
