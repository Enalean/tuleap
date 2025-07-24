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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class ClientInterfaceWithSeveralCallsStub implements ClientInterface
{
    private int $number_of_call;

    public function __construct()
    {
        $this->number_of_call = 0;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->number_of_call++;
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        switch ($this->number_of_call) {
            case 1:
                $body = $stream_factory->createStream(json_encode([['id' => 100]]));
                $link =  "<https://gitlab.example.com/api/v4/wololo?page=1>; rel='next'";
                break;
            case 2:
                $body = $stream_factory->createStream(json_encode([['id' => 200]]));
                $link = "<https://gitlab.example.com/api/v4/wololo?page=2; rel='last'>";
                break;
            default:
                throw new Exception('Too many calls');
        }
        return HTTPFactoryBuilder::responseFactory()->createResponse()->withHeader('link', [$link])->withBody($body);
    }
}
