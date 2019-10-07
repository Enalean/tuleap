<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Tests\REST\ComputedFieldsDefaultValue;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ .'/../TrackerBase.php';

class ComputedFieldsDefaultValueTest extends TrackerBase
{
    public function testComputedFieldHasDefaultValueKey()
    {
        $computed_field_found = false;

        $tracker_representation = $this->tracker_representations[$this->computed_value_tracker_id];
        foreach ($tracker_representation['fields'] as $field) {
            if ($field['type'] === 'computed') {
                $computed_field_found = true;
                $this->assertArrayHasKey('default_value', $field);
            }
        }

        if (! $computed_field_found) {
            $this->fail('Computed field not found to check default value.');
        }
    }
}
