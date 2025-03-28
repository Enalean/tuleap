<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\REST\ReadOnlyAdministrator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('ArtifactFilesTest')]
class ArtifactFilesTest extends \ArtifactFilesTest
{
    #[\PHPUnit\Framework\Attributes\Depends('testPostArtifactFile')]
    public function testOptionsArtifactIdWithUser($file_id): void
    {
        $request  = $this->request_factory->createRequest('OPTIONS', 'artifact_temporary_files/' . $file_id);
        $response = $this->getResponseForReadOnlyUserAdmin($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testAttachFileToPutArtifact')]
    public function testArtifactAttachedFilesGetIdWithUser($file_id): void
    {
        $request  = $this->request_factory->createRequest('GET', 'artifact_files/' . $file_id);
        $response = $this->getResponseForReadOnlyUserAdmin($request);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $data = $json['data'];

        $expected = base64_encode(base64_decode($this->first_file['content']) . $this->second_chunk);

        $this->assertEquals($expected, $data);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testAttachFileToPutArtifact')]
    public function testOptionsArtifactAttachedFilesIdUser($file_id): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('OPTIONS', 'artifact_files/' . $file_id)
        );

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }
}
