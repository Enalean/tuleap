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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldCanBeEasilyMigratedVerifierTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith(['string', 'text', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['text', 'text', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['string', 'string', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['text', 'string', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['string', 'int', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['text', 'int', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['int', 'int', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['int', 'float', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['float', 'int', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['date', 'date', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['date', 'lud', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['lud', 'lud', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['lud', 'aid', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['aid', 'aid', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['aid', 'subby', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['subby', 'subby', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['subby', 'subon', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['subon', 'subon', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['subon', 'cross', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['cross', 'cross', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['luby', 'luby', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['atid', 'atid', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['priority', 'priority', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['burndown', 'burndown', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['computed', 'computed', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['shared', 'shared', true])]
    public function testSimpleTypesAreCompatibles(string $source_type, string $destination_field, bool $are_compatible): void
    {
        $source_type_retrieve      = RetrieveFieldTypeStub::withType($source_type);
        $destination_type_retrieve = RetrieveFieldTypeStub::withType($destination_field);

        $source_field      = $this->createStub(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $destination_field = $this->createStub(\Tuleap\Tracker\FormElement\Field\TrackerField::class);

        $checker = new FieldCanBeEasilyMigratedVerifier(
            $source_type_retrieve,
            $destination_type_retrieve,
        );
        self::assertSame($are_compatible, $checker->canFieldBeEasilyMigrated($source_field, $destination_field));
    }
}
