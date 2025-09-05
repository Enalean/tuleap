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

final class ItemToIndexPlaintextTransformer implements InsertItemsIntoIndex
{
    public function __construct(
        private InsertPlaintextItemsIntoIndex $index_inserter,
        private \Codendi_HTMLPurifier $html_purifier,
        private ContentInterpretor $commonmark_content_interpreter,
    ) {
    }

    #[\Override]
    public function indexItems(ItemToIndex ...$items): void
    {
        $plaintext_items = array_map(
            fn(ItemToIndex $item): PlaintextItemToIndex => $this->transformItemToIndex($item),
            $items
        );
        $this->index_inserter->indexItems(...$plaintext_items);
    }

    private function transformItemToIndex(ItemToIndex $item_to_index): PlaintextItemToIndex
    {
        $content = match ($item_to_index->content_type) {
            ItemToIndex::CONTENT_TYPE_COMMONMARK => $this->commonmark_content_interpreter->getContentStrippedOfTags($item_to_index->content),
            ItemToIndex::CONTENT_TYPE_HTML => $this->html_purifier->purify($item_to_index->content, \Codendi_HTMLPurifier::CONFIG_STRIP_HTML),
            default => $item_to_index->content,
        };

        return new PlaintextItemToIndex($item_to_index->type, $item_to_index->project_id, $content, $item_to_index->metadata);
    }
}
