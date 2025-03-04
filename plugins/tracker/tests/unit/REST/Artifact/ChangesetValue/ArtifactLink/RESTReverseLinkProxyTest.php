<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RESTReverseLinkProxyTest extends TestCase
{
    public function testItBuildsFromPayload(): void
    {
        $reverse = RESTReverseLinkProxy::fromPayload(
            LinkWithDirectionRepresentationBuilder::aReverseLink(101)->withType('_is_child')->build()
        );
        self::assertSame(101, $reverse->getSourceArtifactId());
        self::assertSame('_is_child', $reverse->getType());
    }

    public function testWithDefaultsEmptyType(): void
    {
        $link = RESTReverseLinkProxy::fromPayload(
            LinkWithDirectionRepresentationBuilder::aReverseLink(49)->withType('')->build()
        );
        self::assertSame(49, $link->getSourceArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $link->getType());
    }

    public function testItThrowsWhenTypeIsNull(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        RESTReverseLinkProxy::fromPayload(LinkWithDirectionRepresentationBuilder::aReverseLinkWithNullType(74));
    }
}
