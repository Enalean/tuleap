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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ValidArtifactLinkPayloadBuilderTest extends TestCase
{
    private const PARENT_ARTIFACT_ID = 100;
    private const FIELD_ID           = 242;

    public function testItThrowsWhenAllLinkAndLinksAreUsedInTheSameTime(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withAllLinks(LinkWithDirectionRepresentationBuilder::aReverseLink(48)->build())
            ->withLinks(['id' => 24])
            ->build();

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->expectExceptionMessage('cannot be used at the same time');
        ValidArtifactLinkPayloadBuilder::buildPayloadAndCheckValidity($payload);
    }

    public function testItThrowsWhenNeitherLinksOrAllLinksAreUsed(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)->build();

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->expectExceptionMessage('"links" and/or "parent" or "all_links" key must be defined');
        ValidArtifactLinkPayloadBuilder::buildPayloadAndCheckValidity($payload);
    }

    public function testItThrowsWhenAllLinksIsUsedWithParent(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withAllLinks(LinkWithDirectionRepresentationBuilder::aReverseLink(48)->build())
            ->withParent(self::PARENT_ARTIFACT_ID)
            ->build();

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->expectExceptionMessage('cannot be used at the same time');
        ValidArtifactLinkPayloadBuilder::buildPayloadAndCheckValidity($payload);
    }
}
