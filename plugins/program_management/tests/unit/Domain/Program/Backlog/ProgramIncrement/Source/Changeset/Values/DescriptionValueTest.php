<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveDescriptionValueStub;

final class DescriptionValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const DESCRIPTION_VALUE  = 'unrosed adhamant';
    private const DESCRIPTION_FORMAT = 'text';

    public function testItBuildsFromReplicationAndSynchronizedFields(): void
    {
        $value = DescriptionValue::fromReplicationDataAndSynchronizedFields(
            RetrieveDescriptionValueStub::withValue(self::DESCRIPTION_VALUE, self::DESCRIPTION_FORMAT),
            ReplicationDataBuilder::build(),
            SynchronizedFieldsBuilder::build()
        );
        self::assertEquals(
            ['content' => self::DESCRIPTION_VALUE, 'format' => self::DESCRIPTION_FORMAT],
            $value->getValue()
        );
    }
}
