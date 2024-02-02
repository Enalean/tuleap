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

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferencePresenterTest extends TestCase
{
    public function testWithTitle(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $a_ref->withTitle('New title', null);

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('New title', $new_ref->title);
        self::assertNull($new_ref->title_badge);
    }

    public function testWithTitleBadge(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $a_ref->withTitle('New title', TitleBadgePresenter::buildLabelBadge('badge', 'color'));

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('New title', $new_ref->title);
        self::assertEquals('badge', $new_ref->title_badge->label);
        self::assertEquals('color', $new_ref->title_badge->color);
    }

    public function testWithAdditionalBadges(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $a_ref->withAdditionalBadges(
            [
                AdditionalBadgePresenter::buildSecondary('riri'),
            ]
        );

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('riri', $new_ref->additional_badges[0]->label);
    }

    public function testWithCreationMetadata(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $a_ref->withCreationMetadata(
            new CreatedByPresenter("John Doe", false, ''),
            new TlpRelativeDatePresenter("la date", "absolute", "right", "absolute", "en_US"),
        );

        self::assertEquals(1, $new_ref->id);
        self::assertEquals('John Doe', $new_ref->creation_metadata->created_by->display_name);
        self::assertEquals('la date', $new_ref->creation_metadata->created_on->date);
    }

    public function testWithCreationMetadataWithoutCreatedByPresenter(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $a_ref->withCreationMetadata(
            CreationMetadataPresenter::NO_CREATED_BY_PRESENTER,
            new TlpRelativeDatePresenter("la date", "absolute", "right", "absolute", "en_US"),
        );

        self::assertEquals(1, $new_ref->id);
        self::assertEquals(null, $new_ref->creation_metadata->created_by);
        self::assertEquals('la date', $new_ref->creation_metadata->created_on->date);
    }
}
