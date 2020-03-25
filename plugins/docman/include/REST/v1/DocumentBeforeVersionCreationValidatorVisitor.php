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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Luracast\Restler\RestException;
use Project;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\REST\I18NRestException;

/**
 * @template-implements ItemVisitor<void>
 */
class DocumentBeforeVersionCreationValidatorVisitor implements ItemVisitor
{
    /**
     * @var \Docman_PermissionsManager
     */
    private $permission_manager;
    /**
     * @var ApprovalTableUpdateActionChecker
     */
    private $approval_table_update_action_checker;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    public function __construct(
        \Docman_PermissionsManager $permission_manager,
        ApprovalTableUpdateActionChecker $approval_table_update_action_checker,
        \Docman_ItemFactory $item_factory,
        ApprovalTableRetriever $approval_table_retriever
    ) {
        $this->permission_manager                   = $permission_manager;
        $this->approval_table_update_action_checker = $approval_table_update_action_checker;
        $this->item_factory                         = $item_factory;
        $this->approval_table_retriever             = $approval_table_retriever;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);

        if ($item->getTitle() !== $params['title']
            && $this->item_factory->doesTitleCorrespondToExistingFolder($params['title'], (int) $item->getParentId())) {
            throw new RestException(400, "A folder with same title already exists in the given folder.");
        }
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);

        $project = $params['project'];
        \assert($project instanceof Project);
        if (! $project->usesWiki()) {
            throw new RestException(
                400,
                sprintf('The wiki service of the project: "%s" is not available', $project->getUnixName())
            );
        }

        $this->checkItemNameDoesNotAlreadyExistsInParent($item, $params['title']);
        $this->checkUserCanWrite($params['user'], $item);
        $this->checkDocumentIsNotAlreadyLocked($item, $params);

        if ($this->approval_table_retriever->hasApprovalTable($item)) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'It is not possible to update a wiki page with approval table.')
            );
        }
    }

    public function visitLink(Docman_Link $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);
        $this->checkItemCanBeUpdated($item, $params);
    }

    public function visitFile(Docman_File $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);
        $this->checkItemCanBeUpdated($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);
        $this->checkItemCanBeUpdated($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): void
    {
        $this->checkExpectedType($item, $params['document_type']);
        $this->checkItemCanBeUpdated($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = []): void
    {
        $this->throwItemHasNotTheRightType($params['document_type']);
    }

    /**
     * @psalm-param class-string<Docman_Item> $expected_type
     * @throws I18NRestException
     */
    private function checkExpectedType(Docman_Item $item, string $expected_type) : void
    {
        if (! $item->accept(new DoesItemHasExpectedTypeVisitor($expected_type))) {
            $this->throwItemHasNotTheRightType($expected_type);
        }
    }

    /**
     * @throws I18NRestException
     */
    private function throwItemHasNotTheRightType(string $document_type): void
    {
        throw new I18NRestException(
            400,
            sprintf(
                'The provided item id references an item which is not a %s',
                $document_type
            )
        );
    }

    /**
     * @throws I18NRestException
     */
    private function checkUserCanWrite(\PFUser $user, \Docman_Item $item): void
    {
        if (! $this->permission_manager->userCanWrite($user, $item->getId())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to write this item.')
            );
        }
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws I18NRestException
     * @throws \Tuleap\Docman\ApprovalTable\ApprovalTableException
     * @throws RestException
     */
    private function checkItemCanBeUpdated(Docman_Item $item, array $params): void
    {
        $this->checkItemNameDoesNotAlreadyExistsInParent($item, $params['title']);
        $this->checkUserCanWrite($params['user'], $item);
        $this->checkOptionForApprovalTableAreCorrect($item, $params);
        $this->checkDocumentIsNotAlreadyLocked($item, $params);
    }

    /**
     * @param array        $params
     *
     * @throws \Tuleap\Docman\ApprovalTable\ApprovalTableException
     */
    private function checkOptionForApprovalTableAreCorrect(Docman_Item $item, array $params): void
    {
        $this->approval_table_update_action_checker->checkApprovalTableForItem($params['approval_table_action'], $item);
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     */
    private function checkDocumentIsNotAlreadyLocked(Docman_Item $item, array $params): void
    {
        if ($this->permission_manager->_itemIsLockedForUser($params['user'], (int) $item->getId())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'Document is locked by another user.')
            );
        }
    }

    private function checkItemNameDoesNotAlreadyExistsInParent(Docman_Item $item, string $new_title): void
    {
        if ($new_title === $item->getTitle()) {
            return;
        }

        if ($this->item_factory->doesTitleCorrespondToExistingDocument($new_title, (int) $item->getParentId())) {
            throw new RestException(400, "A file with same title already exists in the given folder.");
        }
    }
}
