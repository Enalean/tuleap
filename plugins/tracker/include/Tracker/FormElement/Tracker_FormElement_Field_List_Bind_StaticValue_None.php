<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All rights reserved
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

use Tuleap\DB\DatabaseUUIDV7Factory;

final class Tracker_FormElement_Field_List_Bind_StaticValue_None extends Tracker_FormElement_Field_List_Bind_StaticValue // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const VALUE_ID     = 100;
    public const XML_VALUE_ID = '';

    public function __construct()
    {
        $id          = self::VALUE_ID;
        $label       = $GLOBALS['Language']->getText('global', 'none');
        $description = '';
        $rank        = 0;
        $is_hidden   = false;

        $uuid_factory = new DatabaseUUIDV7Factory();
        parent::__construct($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $id, $label, $description, $rank, $is_hidden);
    }
}
