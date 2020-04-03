<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List_Bind_StaticValue;

class BindStaticValueUnchanged extends Tracker_FormElement_Field_List_Bind_StaticValue
{
    public const VALUE_ID      = -1;
    private const XML_VALUE_ID = '';

    public function __construct()
    {
        $id          = self::VALUE_ID;
        $label       = dgettext('tuleap-tracker', 'Unchanged');
        $description = '';
        $rank        = 0;
        $is_hidden   = false;

        parent::__construct($id, $label, $description, $rank, $is_hidden);
    }

    public function getXMLId(): string
    {
        return self::XML_VALUE_ID;
    }
}
