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

namespace Tuleap\Docman\DocumentDeletion;

use Docman_Wiki;
use PFUser;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\DocmanReferencedWikiPageRetriever;

class DocmanWikiDeletor
{
    /**
     * @var DocmanReferencedWikiPageRetriever
     */
    private $wiki_page_retriever;

    /**
     * @var \Docman_PermissionsManager
     */
    private $permission_manager;

    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var \Docman_ItemDao
     */
    private $item_dao;

    public function __construct(
        DocmanReferencedWikiPageRetriever $wiki_page_retriever,
        \Docman_PermissionsManager $permission_manager,
        \Docman_ItemFactory $item_factory,
        \Docman_ItemDao $item_dao,
        \EventManager $event_manager
    ) {
        $this->wiki_page_retriever = $wiki_page_retriever;
        $this->permission_manager  = $permission_manager;
        $this->item_factory        = $item_factory;
        $this->item_dao            = $item_dao;
        $this->event_manager       = $event_manager;
    }

    /**
     * @throws DeleteFailedException
     */
    public function deleteWiki(Docman_Wiki $wiki, PFUser $user, bool $delete_referenced_wiki_page): bool
    {
        $wiki_page = $this->wiki_page_retriever->retrieveAssociatedWikiPage($wiki);
        $this->deleteWikiItem($wiki, $user);

        if ($delete_referenced_wiki_page) {
            if ($wiki_page === null) {
                return true;
            }

            if (! $this->item_factory->deleteWikiPage($wiki->getPagename(), $wiki->getGroupId())) {
                throw DeleteFailedException::fromWiki();
            }
        } else {
            $this->restrictAccessToWikiPage($wiki);

            if ($wiki_page && $wiki_page->getId()) {
                $this->event_manager->processEvent(
                    "wiki_page_updated",
                    [
                        'group_id'   => $wiki->getGroupId(),
                        'wiki_page'  => $wiki->getPagename(),
                        'referenced' => false,
                        'user'       => $user
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @throws DeleteFailedException
     */
    private function deleteWikiItem(Docman_Wiki $wiki, PFUser $user): bool
    {
        if ($this->permission_manager->userCanDelete($user, $wiki)) {
            $this->item_factory->delete($wiki);
            return true;
        } else {
            throw DeleteFailedException::fromItem($wiki);
        }
    }

    private function restrictAccessToWikiPage(Docman_Wiki $wiki): void
    {
        $is_still_referenced = $this->item_dao->isWikiPageReferenced(
            $wiki->getPagename(),
            $wiki->getGroupId()
        );

        if (! $is_still_referenced) {
            $id_in_wiki = $this->item_factory->getIdInWikiOfWikiPageItem(
                $wiki->getPagename(),
                $wiki->getGroupId()
            );

            // Restrict access to wiki admins if the page already exists in wiki.
            if ($id_in_wiki !== null) {
                permission_clear_all($wiki->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, false);
                permission_add_ugroup($wiki->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, $GLOBALS['UGROUP_WIKI_ADMIN']);
            }
        }
    }
}
