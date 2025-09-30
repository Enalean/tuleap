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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use CrossReferenceFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CrossReferenceFactory $cross_reference_factory;
    private ReferenceRepresentationBuilder $reference_representation_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->cross_reference_factory = $this->createMock(CrossReferenceFactory::class);
        $this->cross_reference_factory->method('fetchDatas');
        $this->cross_reference_factory->method('getFormattedCrossReferences')->willReturn(
            [
                'source' => [
                    [
                        'url' => 'custom_url',
                        'ref' => 'story #1234',
                    ],
                    [
                        'url' => 'custom_url2',
                        'ref' => 'art #123',
                    ],
                ],
                'target' => [
                    [
                        'url' => 'custom_url3',
                        'ref' => 'rel #1234',
                    ],
                ],
                'both' => [
                    [
                        'url' => 'custom_url4',
                        'ref' => 'epic #123',
                    ],
                ],
            ]
        );

        $this->reference_representation_builder = new ReferenceRepresentationBuilder($this->cross_reference_factory);
    }

    public function testBuildReferenceRepresentationList(): void
    {
        $references = $this->reference_representation_builder->buildReferenceRepresentationList();

        $this->assertCount(4, $references);

        self::assertSame('in', $references[0]->direction);
        self::assertSame('story #1234', $references[0]->ref);
        self::assertSame('custom_url', $references[0]->url);

        self::assertSame('in', $references[1]->direction);
        self::assertSame('art #123', $references[1]->ref);
        self::assertSame('custom_url2', $references[1]->url);

        self::assertSame('out', $references[2]->direction);
        self::assertSame('custom_url3', $references[2]->url);
        self::assertSame('rel #1234', $references[2]->ref);

        self::assertSame('both', $references[3]->direction);
        self::assertSame('custom_url4', $references[3]->url);
        self::assertSame('epic #123', $references[3]->ref);
    }
}
