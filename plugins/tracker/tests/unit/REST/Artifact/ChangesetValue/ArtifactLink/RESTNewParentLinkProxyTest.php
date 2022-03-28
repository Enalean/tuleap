<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

final class RESTNewParentLinkProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromPayload(): void
    {
        $parent = RESTNewParentLinkProxy::fromRESTPayload(['id' => 101]);
        self::assertSame(101, $parent->getParentArtifactId());
    }

    public function dataProviderRejectedPayloads(): array
    {
        return [
            "Missing 'id' key" => [[]],
            'id is null'       => [['id' => null]],
            'is is a string'   => [['id' => 'invalid']],
            'id is a float'    => [['id' => 3.14]],
        ];
    }

    /**
     * @dataProvider dataProviderRejectedPayloads
     */
    public function testItThrowsWhenPayloadIsInvalid(array $payload): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        RESTNewParentLinkProxy::fromRESTPayload($payload);
    }

    public function dataProviderInvalidIds(): array
    {
        return [
            [['id' => 0]],
            [['id' => -1]],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidIds
     */
    public function testItDoesNotForbidInvalidArtifactIds(array $payload): void
    {
        $parent = RESTNewParentLinkProxy::fromRESTPayload($payload);
        self::assertSame($payload['id'], $parent->getParentArtifactId());
    }
}
