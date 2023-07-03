<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Action;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;

final class FieldCanBeEasilyMigratedVerifierTest extends TestCase
{
    /**
     * @testWith ["string", "text", true]
     *           ["text", "text", true]
     *           ["string", "string", true]
     *           ["text", "string", true]
     *           ["string", "int", false]
     *           ["text", "int", false]
     *           ["int", "int", true]
     *           ["int", "float", true]
     *           ["float", "int", true]
     *           ["date", "date", true]
     *           ["date", "lud", false]
     *           ["lud", "lud", true]
     *           ["lud", "aid", false]
     *           ["aid", "aid", true]
     *           ["aid", "subby", false]
     *           ["subby", "subby", true]
     *           ["subby", "subon", false]
     *           ["subon", "subon", true]
     *           ["subon", "cross", false]
     *           ["cross", "cross", true]
     */
    public function testSimpleTypesAreCompatibles(string $source_type, string $destination_field, bool $are_compatible): void
    {
        $source_type_retrieve      = RetrieveFieldTypeStub::withType($source_type);
        $destination_type_retrieve = RetrieveFieldTypeStub::withType($destination_field);

        $source_field      = $this->createStub(\Tracker_FormElement_Field::class);
        $destination_field = $this->createStub(\Tracker_FormElement_Field::class);

        $checker = new FieldCanBeEasilyMigratedVerifier(
            $source_type_retrieve,
            $destination_type_retrieve,
        );
        self::assertSame($are_compatible, $checker->canFieldBeEasilyMigrated($source_field, $destination_field));
    }
}
