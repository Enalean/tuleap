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

namespace Tuleap\Tracker\FormElement\Field\List\Bind;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBindValue;

final class BindStaticValueUnchanged extends ListFieldStaticBindValue
{
    public const int VALUE_ID = -1;

    public function __construct()
    {
        $id          = self::VALUE_ID;
        $label       = dgettext('tuleap-tracker', 'Unchanged');
        $description = '';
        $rank        = 0;
        $is_hidden   = false;

        $uuid_factory = new DatabaseUUIDV7Factory();
        parent::__construct($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $id, $label, $description, $rank, $is_hidden);
    }
}
