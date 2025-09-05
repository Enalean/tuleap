<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Markdown;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Project;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class CommonMarkInterpreterController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ContentInterpretor
     */
    private $interpreter;
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        ContentInterpretor $interpreter,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->interpreter      = $interpreter;
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(Project::class);
        assert($project instanceof Project);
        $parsed_body = $request->getParsedBody();

        if (
            ! is_array($parsed_body)
            || ! isset($parsed_body['content'])
        ) {
            return $this->response_factory->createResponse(400)->withHeader('Content-Type', 'text/plain')->withBody(
                $this->stream_factory->createStream('Bad request: There is no content to interpret')
            );
        }
        $interpreted_content = $this->interpreter->getInterpretedContentWithReferences(
            $parsed_body['content'],
            (int) $project->getGroupId()
        );
        return $this->response_factory->createResponse()->withHeader(
            'Content-Type',
            'text/html; charset=UTF-8'
        )->withBody(
            $this->stream_factory->createStream(
                $interpreted_content
            )
        );
    }
}
