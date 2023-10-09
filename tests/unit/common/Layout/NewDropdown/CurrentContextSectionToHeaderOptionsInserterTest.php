<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Layout\NewDropdown;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Option\Option;

class CurrentContextSectionToHeaderOptionsInserterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesANewSection(): void
    {
        $link = Mockery::mock(NewDropdownLinkPresenter::class);

        $section = (new CurrentContextSectionToHeaderOptionsInserter())
            ->addLinkToCurrentContextSection('Section label', $link, Option::nothing(NewDropdownLinkSectionPresenter::class))
            ->unwrapOr(null);

        self::assertEquals('Section label', $section->label);
        self::assertEquals($link, $section->links[0]);
    }

    public function testItAddsLinkToAlreadyExistingSection(): void
    {
        $link          = Mockery::mock(NewDropdownLinkPresenter::class);
        $existing_link = Mockery::mock(NewDropdownLinkPresenter::class);

        $existing = Option::fromValue(
            new NewDropdownLinkSectionPresenter(
                'Existing section',
                [$existing_link]
            ),
        );

        $section = (new CurrentContextSectionToHeaderOptionsInserter())
            ->addLinkToCurrentContextSection('Section label', $link, $existing)
            ->unwrapOr(null);

        self::assertEquals('Existing section', $section->label);
        self::assertEquals($existing_link, $section->links[0]);
        self::assertEquals($link, $section->links[1]);
    }
}
