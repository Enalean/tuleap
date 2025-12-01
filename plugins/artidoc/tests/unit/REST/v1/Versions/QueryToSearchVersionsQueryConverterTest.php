<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1\Versions;

use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\Json\encode;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class QueryToSearchVersionsQueryConverterTest extends TestCase
{
    /**
     * @return Ok<SearchVersionsQuery>|Err<Fault>
     */
    private function convert(array|string $query): Ok|Err
    {
        $converter = new QueryToSearchVersionsQueryConverter(ValinorMapperBuilderFactory::mapperBuilder()->mapper());
        return $converter->convert(encode($query));
    }

    public function testItBuildsASearchVersionsQueryFromJSON(): void
    {
        $target_version_id     = 14200;
        $search_versions_query = $this->convert(['versions_ids' => [$target_version_id]]);

        self::assertTrue(Result::isOk($search_versions_query));
        self::assertSame([$target_version_id], $search_versions_query->value->versions_ids);
    }

    public function testItReturnsAMalformedSearchVersionsQueryFaultWhenMoreThanOneVersionIdIsProvided(): void
    {
        $search_versions_query = $this->convert(['versions_ids' => [14200, 14201]]);

        self::assertTrue(Result::isErr($search_versions_query));
        self::assertInstanceOf(MalformedSearchVersionsQueryFault::class, $search_versions_query->error);
    }

    public function testItReturnsAMalformedSearchVersionsQueryFaultWhenTheQueryIsMalformed(): void
    {
        $search_versions_query = $this->convert(['target_versions' => [14200]]);

        self::assertTrue(Result::isErr($search_versions_query));
        self::assertInstanceOf(MalformedSearchVersionsQueryFault::class, $search_versions_query->error);
    }

    public function testItReturnsAMalformedSearchVersionsQueryFaultWhenTheQueryIsNotAValidJSONString(): void
    {
        $search_versions_query = $this->convert('Please find version with id 14200');

        self::assertTrue(Result::isErr($search_versions_query));
        self::assertInstanceOf(MalformedSearchVersionsQueryFault::class, $search_versions_query->error);
    }
}
