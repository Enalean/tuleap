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

use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueStub;
use Tuleap\ProgramManagement\Tests\Stub\TitleFieldReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TitleValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_VALUE = 'uncomprehended Sinupallialia';

    public function testItBuildsFromTitleReference(): void
    {
        $value = TitleValue::fromTitleReference(
            RetrieveTitleValueStub::withValue(self::TITLE_VALUE),
            TitleFieldReferenceStub::withDefaults()
        );
        self::assertSame(self::TITLE_VALUE, $value->getValue());
    }
}
