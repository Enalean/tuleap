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

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTableFileDao;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_LinkVersionFactory;
use Docman_Wiki;
use LogicException;
use Tuleap\Docman\Item\ItemVisitor;

/**
 * @template-implements ItemVisitor<bool>
 */
final class HasPotentiallyCorruptedApprovalTable implements ItemVisitor
{
    /**
     * @var Docman_ApprovalTableFileDao
     */
    private $approval_table_file_dao;
    /**
     * @var Docman_LinkVersionFactory
     */
    private $link_version_factory;

    public function __construct(
        Docman_ApprovalTableFileDao $approval_table_file_dao,
        Docman_LinkVersionFactory $link_version_factory
    ) {
        $this->approval_table_file_dao = $approval_table_file_dao;
        $this->link_version_factory    = $link_version_factory;
    }

    public function visitFolder(Docman_Folder $item, array $params = []) : bool
    {
        return false;
    }

    public function visitWiki(Docman_Wiki $item, array $params = []) : bool
    {
        return false;
    }

    public function visitLink(Docman_Link $item, array $params = []) : bool
    {
        if ($params['approval_table'] !== null) {
            return false;
        }

        if ($params['version_number'] === null) {
            $version = $item->getCurrentVersion();
        } else {
            $version = $this->link_version_factory->getSpecificVersion($item, $params['version_number']);
        }

        if ($version === null) {
            return false;
        }

        $rows = $this->approval_table_file_dao->getTableById($version->getId());
        foreach ($rows as $row) {
            return (bool) $row['might_be_corrupted'];
        }

        return false;
    }

    public function visitFile(Docman_File $item, array $params = []) : bool
    {
        $table = $params['approval_table'];
        return $table !== null  && $table->isPotentiallyCorrupted();
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []) : bool
    {
        return $this->visitFile($item, $params);
    }

    /**
     * @psalm-return never-return
     */
    public function visitEmpty(Docman_Empty $item, array $params = []) : bool
    {
        $this->rejectItemThatCannotHaveAnApprovalTable($item);
    }

    /**
     * @psalm-return never-return
     */
    public function visitItem(Docman_Item $item, array $params = []) : bool
    {
        $this->rejectItemThatCannotHaveAnApprovalTable($item);
    }

    /**
     * @psalm-return never-return
     */
    private function rejectItemThatCannotHaveAnApprovalTable(Docman_Item $item) : void
    {
        throw new LogicException($item->getType() . ' cannot have an approval table');
    }
}
