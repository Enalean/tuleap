<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Reference;

use Embed\Http\DispatcherInterface;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;

class ReferenceOpenGraphDispatcher implements DispatcherInterface
{

    /**
     * Dispatch an url.
     *
     * @param Url $url
     *
     * @return Response
     */
    public function dispatch(Url $url)
    {
        try {
            $http_client = new \Http_Client();
            $http_client->addOptions([
                CURLOPT_URL             => $url->__toString(),
                CURLOPT_SSL_VERIFYPEER  => true,
            ]);

            $http_client->doRequest();

            $response = new Response(
                $url,
                Url::create($http_client->getInfo(CURLINFO_EFFECTIVE_URL)),
                $http_client->getInfo(CURLINFO_HTTP_CODE),
                $http_client->getInfo(CURLINFO_CONTENT_TYPE),
                $http_client->getLastResponse(),
                [],
                []
            );

            $http_client->close();

            return $response;
        } catch (\Http_ClientException $exception) {
            return new Response($url, $url, 400, null, null, [], []);
        }
    }

    /**
     * Resolve multiple image urls at once.
     *
     * Not implemented yet.
     *
     * @param Url[] $urls
     *
     * @return ImageResponse[]
     */
    public function dispatchImages(array $urls)
    {
        return [];
    }
}
