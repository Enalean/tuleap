<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Codendi_HTMLPurifier;

class FieldPresenter
{
    public $id;
    public $name;
    public $purified_description;
    public $required_value;
    public $required_label;
    public $type_is_line;
    public $type_label;
    public $rank_on_screen;
    public $edit_btn;
    public $delete_btn;
    /**
     * @var bool
     */
    public $is_system;

    public function __construct(
        $id,
        $name,
        $description,
        $required_value,
        $required_label,
        $type_value,
        $type_label,
        $rank_on_screen,
        bool $is_system
    ) {
        $this->id                   = $id;
        $this->name                 = $name;
        $this->purified_description = Codendi_HTMLPurifier::instance()->purify($description, CODENDI_PURIFIER_LIGHT);
        $this->required_value       = $required_value;
        $this->required_label       = $required_label;
        $this->type_is_line         = $type_value === 'line';
        $this->type_label           = $type_label;
        $this->rank_on_screen       = $rank_on_screen;
        $this->is_system            = $is_system;

        $this->edit_btn   = $GLOBALS['Language']->getText('admin_desc_fields', 'edit_btn');
        $this->delete_btn = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_btn');
    }
}
