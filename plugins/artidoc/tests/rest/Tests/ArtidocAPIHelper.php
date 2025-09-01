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
        string $rest_user_name,
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
}
