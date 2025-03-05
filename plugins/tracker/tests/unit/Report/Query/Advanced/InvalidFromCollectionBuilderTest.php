<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced;

use LogicException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidFromCollectionBuilderTest extends TestCase
{
    public function testItThrowsWhenCalled(): void
    {
        $builder = new InvalidFromCollectionBuilder();
        self::expectException(LogicException::class);
        $builder->buildCollectionOfInvalidFrom(
            new From(new FromProject('@project', new FromProjectEqual('self')), null),
            UserTestBuilder::buildWithDefaults(),
        );
    }
}
