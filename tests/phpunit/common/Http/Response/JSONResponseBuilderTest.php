<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use JsonException;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;

final class JSONResponseBuilderTest extends TestCase
{
    public function testCreateAJSONResponse(): void
    {
        $builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $data_to_encode = new class {
            public $a = 123;
        };

        $response = $builder->fromData($data_to_encode);

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"a":123}', $response->getBody()->getContents());
    }

    public function testRejectNotJSONEncodableData(): void
    {
        $builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $data_to_encode = fopen('php://memory', 'rb');

        $this->expectException(JsonException::class);
        $builder->fromData($data_to_encode);
    }
}
