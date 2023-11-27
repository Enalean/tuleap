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

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\Routing\ProjectRetrieverMiddleware;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Helpers\NoopSapiEmitter;

final class CommonMarkInterpreterControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CommonMarkInterpreterController $interpreter_controller;
    private CommonMarkInterpreter $interpreter;

    public function setUp(): void
    {
        $this->interpreter            = CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance());
        $this->interpreter_controller = new CommonMarkInterpreterController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->interpreter,
            new NoopSapiEmitter(),
            new ProjectRetrieverMiddleware($this->createMock(ProjectRetriever::class))
        );
    }

    public function testItReturns400WhenThereIsNoContent(): void
    {
        $request = (new NullServerRequest())->withAttribute(\Project::class, new \Project(['group_id' => 102]));

        $response = $this->interpreter_controller->handle($request);
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('Bad request: There is no content to interpret', $response->getBody()->getContents());
    }

    public function testItReturnsTheInterpretedContent(): void
    {
        $project = new \Project(['group_id' => 102]);
        $content = '# Honk honk, I am a *little* _joker_';
        $request = (new NullServerRequest())->withAttribute(\Project::class, $project)->withParsedBody(
            (['content' => $content])
        );

        $response = $this->interpreter_controller->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            $this->interpreter->getInterpretedContentWithReferences($content, $project->getGroupId()),
            $this->interpreter_controller->handle($request)->getBody()->getContents()
        );
    }
}
