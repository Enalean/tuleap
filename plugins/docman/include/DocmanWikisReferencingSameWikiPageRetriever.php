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

namespace Tuleap\Docman;

use PFUser;
use Tuleap\PHPWiki\WikiPage;
use Tuleap\wiki\Events\ItemReferencingWikiPageRepresentation;

class DocmanWikisReferencingSameWikiPageRetriever
{
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @var \Docman_PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        \Docman_PermissionsManager $permissions_manager
    ) {
        $this->item_factory        = $item_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public function retrieveWikiDocuments(
        WikiPage $wiki_page,
        PFUser $user
    ): array {
        $wikis_referencing_wiki_page = [];

        $wikis = $this->item_factory->getWikiPageReferencers($wiki_page->getPagename(), (string) $wiki_page->getGid());

        foreach ($wikis as $wiki) {
            if ($this->permissions_manager->userCanRead($user, $wiki->getId())) {
                $wikis_referencing_wiki_page[] = new ItemReferencingWikiPageRepresentation(
                    (int) $wiki->getId(),
                    (string) $wiki->getTitle()
                );
            }
        }

        return $wikis_referencing_wiki_page;
    }
}
