<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

use RestBase;

final class ProjectFileResourceTest extends RestBase
{
    public function testPostProjectFile(): void
    {
        $file_creation_body_content = json_encode([
            'file_name' => 'my file',
            'file_size' => 1234,
        ]);

        $request  = $this->request_factory->createRequest('POST', 'project_files')->withBody(
            $this->stream_factory->createStream($file_creation_body_content)
        );
        $response = $this->getResponse($request);

        $this->assertEquals(201, $response->getStatusCode());

        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals("/uploads/project/file/1", $file_representation['upload_href']);
    }
}
