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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RESTNewParentLinkProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public static function dataProviderValidPayloads(): array
    {
        return [
            'id is an integer cast to string' => [['id' => '29']],
            'id is an integer'                => [['id' => 101]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidPayloads')]
    public function testItBuildsFromPayload(array $payload): void
    {
        $parent = RESTNewParentLinkProxy::fromRESTPayload($payload);
        self::assertSame((int) $payload['id'], $parent->getParentArtifactId());
    }

    public static function dataProviderRejectedPayloads(): array
    {
        return [
            "Missing 'id' key"                         => [[]],
            'id is null'                               => [['id' => null]],
            'is is a string that does not cast to int' => [['id' => 'invalid']],
            'is is a float cast to string'             => [['id' => '34.4031']],
            'id is a float'                            => [['id' => 3.14]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderRejectedPayloads')]
    public function testItThrowsWhenPayloadIsInvalid(array $payload): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        RESTNewParentLinkProxy::fromRESTPayload($payload);
    }

    public static function dataProviderInvalidIds(): array
    {
        return [
            [['id' => 0]],
            [['id' => -1]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidIds')]
    public function testItDoesNotForbidInvalidArtifactIds(array $payload): void
    {
        $parent = RESTNewParentLinkProxy::fromRESTPayload($payload);
        self::assertSame($payload['id'], $parent->getParentArtifactId());
    }
}
