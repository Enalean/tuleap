<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\WASM;

use ColinODell\PsrTestLogger\TestLogger;
use CuyZ\Valinor\MapperBuilder;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use function Psl\Json\encode as psl_json_encode;

final class ProcessWASMResponseTest extends TestCase
{
    private TestLogger $logger;
    private ProcessWASMResponse $processor;

    protected function setUp(): void
    {
        $this->logger    = new TestLogger();
        $this->processor = new ProcessWASMResponse($this->logger, (new MapperBuilder())->allowPermissiveTypes()->mapper());
    }

    public function testItReturnsErrIfResponseIsErr(): void
    {
        $result = $this->processor->processResponse(Result::err(Fault::fromMessage('WASM Fault')));
        self::assertTrue($this->logger->hasWarning('WASM Fault'));
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsErrIfResponseIsOkButMapperThrow(): void
    {
        $result = $this->processor->processResponse(Result::ok('invalid-data'));
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsOkIfResponseIsOkAndDataValid(): void
    {
        $value           = new ArtifactValuesRepresentation();
        $value->field_id = 254;
        $value->value    = "Hello!";
        $result          = $this->processor->processResponse(Result::ok(psl_json_encode(
            new WASMResponseRepresentation([$value], new NewChangesetCommentRepresentation("My comment", "text"))
        )));
        self::assertTrue(Result::isOk($result));
    }
}
