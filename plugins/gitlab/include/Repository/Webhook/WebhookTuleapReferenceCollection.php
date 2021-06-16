<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

/**
 * @psalm-immutable
 */
final class WebhookTuleapReferenceCollection
{
    /**
     * @var WebhookTuleapReference[]
     * @psalm-var list<WebhookTuleapReference>
     */
    private array $tuleap_references;

    /**
     * @param WebhookTuleapReference[] ...$references_collections
     */
    private function __construct(array ...$references_collections)
    {
        $references = [];
        foreach (array_merge(...$references_collections) as $reference) {
            $references[$reference->getId()] = $reference;
        }
        $this->tuleap_references = array_values($references);
    }

    public static function fromReferences(WebhookTuleapReference ...$references): self
    {
        return new self($references);
    }

    public static function aggregateCollections(self ...$collections): self
    {
        $references_collections = [];
        foreach ($collections as $collection) {
            $references_collections[] = $collection->getTuleapReferences();
        }

        return new self(...$references_collections);
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @return WebhookTuleapReference[]
     * @psalm-return list<WebhookTuleapReference>
     */
    public function getTuleapReferences(): array
    {
        return $this->tuleap_references;
    }
}
