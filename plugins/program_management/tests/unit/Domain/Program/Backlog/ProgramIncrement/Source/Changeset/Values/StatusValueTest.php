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

use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\StatusFieldReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_LABEL  = 'diaster';
    private const SECOND_LABEL = 'tolter';

    public function testItBuildsFromStatusReference(): void
    {
        $value  = StatusValue::fromStatusReference(
            RetrieveStatusValuesStub::withValues(self::FIRST_LABEL, self::SECOND_LABEL),
            StatusFieldReferenceStub::withDefaults()
        );
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $value->getListValues());
        self::assertContains(self::FIRST_LABEL, $labels);
        self::assertContains(self::SECOND_LABEL, $labels);
    }
}
