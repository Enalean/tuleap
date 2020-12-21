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

namespace Tuleap\Reference;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CrossReferencePresenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithTitle(): void
    {
        $a_ref = new CrossReferencePresenter(
            1,
            'type',
            'title',
            'url',
            'delete_url',
            1,
            'whatever',
            null,
            [],
        );

        $new_ref = $a_ref->withTitle('New title', null);

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('New title', $new_ref->title);
        self::assertNull($new_ref->title_badge);
    }

    public function testWithTitleBadge(): void
    {
        $a_ref = new CrossReferencePresenter(
            1,
            'type',
            'title',
            'url',
            'delete_url',
            1,
            'whatever',
            null,
            [],
        );

        $new_ref = $a_ref->withTitle('New title', new TitleBadgePresenter('badge', 'color'));

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('New title', $new_ref->title);
        self::assertEquals('badge', $new_ref->title_badge->label);
        self::assertEquals('color', $new_ref->title_badge->color);
    }

    public function testWithAdditionalBadges(): void
    {
        $a_ref = new CrossReferencePresenter(
            1,
            'type',
            'title',
            'url',
            'delete_url',
            1,
            'whatever',
            null,
            [],
        );

        $new_ref = $a_ref->withAdditionalBadges([new AdditionalBadgePresenter('riri')]);

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('riri', $new_ref->additional_badges[0]->label);
    }
}
