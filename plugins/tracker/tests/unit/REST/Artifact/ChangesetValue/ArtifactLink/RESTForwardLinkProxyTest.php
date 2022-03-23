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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

final class RESTForwardLinkProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFromPayload()
    {
        $link = RESTForwardLinkProxy::fromPayload(['id' => 101, 'type' => '_is_child']);
        self::assertSame(101, $link->getTargetArtifactId());
        self::assertSame('_is_child', $link->getType());
    }

    public function testItThrowsWhenThereIsNoArtifactIdInPayload(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);

        RESTForwardLinkProxy::fromPayload(['type' => '_is_child']);
    }

    public function testItDefaultsToNullWhenNoType(): void
    {
        $link = RESTForwardLinkProxy::fromPayload(['id' => 75]);
        self::assertSame(75, $link->getTargetArtifactId());
        self::assertNull($link->getType());
    }
}
