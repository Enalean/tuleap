<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RESTForwardLinkProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFromPayload(): void
    {
        $link = RESTForwardLinkProxy::fromPayload(['id' => 101, 'type' => ArtifactLinkField::TYPE_IS_CHILD]);
        self::assertSame(101, $link->getTargetArtifactId());
        self::assertSame(ArtifactLinkField::TYPE_IS_CHILD, $link->getType());
    }

    public function testItThrowsWhenThereIsNoArtifactIdInPayload(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);

        RESTForwardLinkProxy::fromPayload(['type' => ArtifactLinkField::TYPE_IS_CHILD]);
    }

    public function testItUsesDefaultTypeWhenTypeIsMissing(): void
    {
        $link = RESTForwardLinkProxy::fromPayload(['id' => 75]);
        self::assertSame(75, $link->getTargetArtifactId());
        self::assertSame(ArtifactLinkField::DEFAULT_LINK_TYPE, $link->getType());
    }

    public function testItThrowsExceptionIfTheTypeKeyInAllLinksPayloadIsNotAString(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);

        $all_link_payload = new LinkWithDirectionRepresentation(121, LinkDirection::FORWARD->value, null);

        RESTForwardLinkProxy::fromAllLinksPayload($all_link_payload);
    }

    public function testTheTypeAttributeIsDefaultWhenTheTypeKeyFromAllLinksPayloadIsAnEmptyString(): void
    {
        $all_link_payload = new LinkWithDirectionRepresentation(121, LinkDirection::FORWARD->value, ArtifactLinkField::DEFAULT_LINK_TYPE);

        $forward_links = RESTForwardLinkProxy::fromAllLinksPayload($all_link_payload);

        self::assertSame(121, $forward_links->getTargetArtifactId());
        self::assertSame(ArtifactLinkField::DEFAULT_LINK_TYPE, $forward_links->getType());
    }

    public function testItReturnsTheProxyObjectWithTheIdAndType(): void
    {
        $all_link_payload = new LinkWithDirectionRepresentation(121, LinkDirection::FORWARD->value, '_is_child');

        $forward_links = RESTForwardLinkProxy::fromAllLinksPayload($all_link_payload);

        self::assertSame(121, $forward_links->getTargetArtifactId());
        self::assertSame(ArtifactLinkField::TYPE_IS_CHILD, $forward_links->getType());
    }
}
