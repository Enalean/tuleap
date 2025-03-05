<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Http\Response;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\Json\decode as psl_json_decode;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RestlerErrorResponseBuilderTest extends TestCase
{
    public function testItCreateAResponseWithCorrespondingErrorMessage(): void
    {
        $builder = new RestlerErrorResponseBuilder(new JSONResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        ));

        $response = $builder->build(404);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found', $response->getReasonPhrase());
        $body = psl_json_decode($response->getBody()->getContents());
        self::assertEqualsCanonicalizing([
            'error' => [
                'code' => 404,
                'message' => 'Not Found',
            ],
        ], $body);
    }

    public function testItCreateAResponseWithMessage(): void
    {
        $builder = new RestlerErrorResponseBuilder(new JSONResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        ));

        $response = $builder->build(400, 'My message');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Bad Request', $response->getReasonPhrase());
        $body = psl_json_decode($response->getBody()->getContents());
        self::assertEqualsCanonicalizing([
            'error' => [
                'code' => 400,
                'message' => 'Bad Request: My message',
            ],
        ], $body);
    }
}
