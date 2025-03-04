<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchCommon\Index;

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemToIndexPlaintextTransformerTest extends TestCase
{
    public function testTransformsItems(): void
    {
        $items          = [
            new ItemToIndex('A', 102, '<b>HTML</b>', 'html', ['a' => 'a']),
            new ItemToIndex('A', 102, '*commonmark*', 'commonmark', ['a' => 'a']),
            new ItemToIndex('A', 102, 'text', 'plaintext', ['a' => 'a']),
        ];
        $expected_items = [
            new PlaintextItemToIndex('A', 102, 'HTML', ['a' => 'a']),
            new PlaintextItemToIndex('A', 102, 'commonmark', ['a' => 'a']),
            new PlaintextItemToIndex('A', 102, 'text', ['a' => 'a']),
        ];

        $stub_plaintext_item_inserter = new class implements InsertPlaintextItemsIntoIndex
        {
            public array $indexed_items = [];

            public function indexItems(PlaintextItemToIndex ...$items): void
            {
                $this->indexed_items = array_merge($this->indexed_items, $items);
            }
        };

        $html_purifier = $this->createStub(\Codendi_HTMLPurifier::class);
        $html_purifier->method('purify')->willReturn('HTML');

        $transformer = new ItemToIndexPlaintextTransformer(
            $stub_plaintext_item_inserter,
            $html_purifier,
            new class implements ContentInterpretor {
                public function getInterpretedContent(string $content): string
                {
                    return 'not expected';
                }

                public function getInterpretedContentWithReferences(string $content, int $project_id): string
                {
                    return 'not expected';
                }

                public function getContentStrippedOfTags(string $content): string
                {
                    return 'commonmark';
                }
            }
        );

        $transformer->indexItems(...$items);

        self::assertEquals($expected_items, $stub_plaintext_item_inserter->indexed_items);
    }
}
