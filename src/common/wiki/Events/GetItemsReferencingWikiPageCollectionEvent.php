<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\wiki\Events;

use Tuleap\Event\Dispatchable;
use Tuleap\PHPWiki\WikiPage;

class GetItemsReferencingWikiPageCollectionEvent implements Dispatchable
{
    public const NAME = "getItemsReferencingWikiPageCollectionEvent";

    /**
     * @var WikiPage
     */
    private $wiki_page;

    /**
     * @var array
     */
    private $items_referencing_wiki_page = [];

    /**
     * @var \PFUser
     */
    private $user;

    public function __construct(WikiPage $wiki_page, \PFUser $user)
    {
        $this->wiki_page = $wiki_page;
        $this->user      = $user;
    }

    public function addItemsReferencingWikiPage(array $items_referencing_wiki_page): void
    {
        $this->items_referencing_wiki_page = array_merge($this->items_referencing_wiki_page, $items_referencing_wiki_page);
    }

    public function getItemsReferencingWikiPage(): array
    {
        return $this->items_referencing_wiki_page;
    }

    public function getWikiPage(): WikiPage
    {
        return $this->wiki_page;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
