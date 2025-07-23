<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestHandlerInterfaceStub implements \Psr\Http\Server\RequestHandlerInterface
{
    private ?ServerRequestInterface $captured_request = null;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->captured_request = $request;

        return new Response();
    }

    public function hasBeenCalled(): bool
    {
        return $this->captured_request !== null;
    }

    public function getCapturedRequest(): ServerRequestInterface
    {
        if (! $this->captured_request) {
            throw new \Exception('No captured request, handle() has not been called.');
        }

        return $this->captured_request;
    }
}
