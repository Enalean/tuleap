<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Tests;

use Psl\Json;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RequestWrapper;

final readonly class ArtidocAPIHelper
{
    public function __construct(
        private RequestWrapper $request_wrapper,
        private RequestFactoryInterface $request_factory,
        private StreamFactoryInterface $stream_factory,
    ) {
    }

    public function createArtidoc(
        int $parent_folder_id,
        string $title,
        DocumentPermissions $permissions,
        string $rest_user_name = BaseTestDataBuilder::TEST_USER_1_NAME,
    ): array {
        $post_item_response = $this->request_wrapper->getResponseByName(
            $rest_user_name,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $parent_folder_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(
                        Json\encode(
                            [
                                'type'                   => 'artidoc',
                                'title'                  => $title,
                                'permissions_for_groups' => $permissions,
                            ],
                        ),
                    ),
                ),
        );
        if ($post_item_response->getStatusCode() !== 201) {
            throw new \RuntimeException(sprintf('Could not create artidoc with title "%s"', $title));
        }
        return Json\decode($post_item_response->getBody()->getContents());
    }

    public function importExistingArtifactInArtidoc(int $artidoc_id, string $rest_user_name, int ...$artifact_ids): void
    {
        foreach ($artifact_ids as $artifact_id) {
            $post_response = $this->request_wrapper->getResponseByName(
                $rest_user_name,
                $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                    $this->stream_factory->createStream(
                        Json\encode(
                            [
                                'artidoc_id' => $artidoc_id,
                                'section'    => [
                                    'import'   => [
                                        'artifact' => ['id' => $artifact_id],
                                        'level'    => 1,
                                    ],
                                    'position' => null,
                                    'content'  => null,
                                ],
                            ]
                        )
                    )
                ),
            );
            if ($post_response->getStatusCode() !== 200) {
                throw new \RuntimeException(
                    sprintf('Could not import artifact #%s in artidoc #%s', $artifact_id, $artidoc_id)
                );
            }
        }
    }

    public function getArtidocSections(
        int $artidoc_id,
        string $rest_user_name = BaseTestDataBuilder::TEST_USER_1_NAME,
    ): SectionsRESTCollection {
        $response = $this->request_wrapper->getResponseByName(
            $rest_user_name,
            $this->request_factory->createRequest('GET', 'artidoc/' . $artidoc_id . '/sections')
        );
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf('Could not get the sections of artidoc #%s', $artidoc_id));
        }
        return new SectionsRESTCollection($artidoc_id, Json\decode($response->getBody()->getContents()));
    }
}
