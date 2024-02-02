<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Tuleap\Test\PHPUnit\TestCase;

final class AdditionalBadgePresenterTest extends TestCase
{
    public function testBuildPrimary(): void
    {
        $badge = AdditionalBadgePresenter::buildPrimary('Le label');

        self::assertEquals('Le label', $badge->label);
        self::assertTrue($badge->is_primary);
        self::assertFalse($badge->is_secondary);
        self::assertFalse($badge->is_danger);
        self::assertFalse($badge->is_success);
        self::assertFalse($badge->is_plain);
    }

    public function testBuildPrimaryPlain(): void
    {
        $badge = AdditionalBadgePresenter::buildPrimaryPlain('Le label');

        self::assertEquals('Le label', $badge->label);
        self::assertTrue($badge->is_primary);
        self::assertFalse($badge->is_secondary);
        self::assertFalse($badge->is_danger);
        self::assertFalse($badge->is_success);
        self::assertTrue($badge->is_plain);
    }

    public function testBuildSecondary(): void
    {
        $badge = AdditionalBadgePresenter::buildSecondary('Le label');

        self::assertEquals('Le label', $badge->label);
        self::assertFalse($badge->is_primary);
        self::assertTrue($badge->is_secondary);
        self::assertFalse($badge->is_danger);
        self::assertFalse($badge->is_success);
        self::assertFalse($badge->is_plain);
    }

    public function testBuildDanger(): void
    {
        $badge = AdditionalBadgePresenter::buildDanger('Le label');

        self::assertEquals('Le label', $badge->label);
        self::assertFalse($badge->is_primary);
        self::assertFalse($badge->is_secondary);
        self::assertTrue($badge->is_danger);
        self::assertFalse($badge->is_success);
        self::assertFalse($badge->is_plain);
    }

    public function testBuildSuccess(): void
    {
        $badge = AdditionalBadgePresenter::buildSuccess('Le label');

        self::assertEquals('Le label', $badge->label);
        self::assertFalse($badge->is_primary);
        self::assertFalse($badge->is_secondary);
        self::assertFalse($badge->is_danger);
        self::assertTrue($badge->is_success);
        self::assertFalse($badge->is_plain);
    }
}
