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

use Codendi_HTMLPurifier;

class FieldPresenter
{
    public $id;
    public $name;
    public $purified_description;
    public $required;
    public $type;
    public $rank_on_screen;
    public $edit_btn;
    public $delete_btn;

    public function __construct(
        $id,
        $name,
        $description,
        $required,
        $type,
        $rank_on_screen
    ) {
        $this->id             = $id;
        $this->name           = $name;
        $this->purified_description    = Codendi_HTMLPurifier::instance()->purify($description, CODENDI_PURIFIER_LIGHT);
        $this->required       = $required;
        $this->type           = $type;
        $this->rank_on_screen = $rank_on_screen;

        $this->edit_btn   = $GLOBALS['Language']->getText('admin_desc_fields', 'edit_btn');
        $this->delete_btn = $GLOBALS['Language']->getText('admin_desc_fields', 'delete_btn');
    }
}
