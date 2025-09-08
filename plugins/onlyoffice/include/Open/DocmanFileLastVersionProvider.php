<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\FilenamePattern\RetrieveFilenamePattern;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class DocmanFileLastVersionProvider implements ProvideDocmanFileLastVersion
{
    public function __construct(
        private \Docman_ItemFactory $item_factory,
        private \Docman_VersionFactory $version_factory,
        private RetrieveFilenamePattern $filename_pattern_retriever,
        private ApprovalTableRetriever $approval_table_retriever,
        private \Docman_LockFactory $lock_factory,
    ) {
    }

    /**
     * @psalm-return Ok<DocmanFileLastVersion>|Err<Fault>
     */
    #[\Override]
    public function getLastVersionOfAFileUserCanAccess(\PFUser $user, int $item_id): Ok|Err
    {
        $item = $this->item_factory->getItemFromDb($item_id);

        if ($item === null) {
            return Result::err(Fault::fromMessage(sprintf('Item #%d does not exist', $item_id)));
        }

        $is_item_a_file = $item->accept(new DoesItemHasExpectedTypeVisitor(\Docman_File::class));
        if (! $is_item_a_file) {
            return Result::err(Fault::fromMessage(sprintf('Item #%d is not a file', $item_id)));
        }

        $project_id                 = $item->getGroupId();
        $docman_permissions_manager = \Docman_PermissionsManager::instance($project_id);
        if (! $docman_permissions_manager->userCanAccess($user, $item_id)) {
            return Result::err(Fault::fromMessage(sprintf('User #%d cannot access file #%d', $user->getId(), $item_id)));
        }

        $version = $this->version_factory->getCurrentVersionForItem($item);
        if ($version === null) {
            return Result::err(Fault::fromMessage(sprintf('Cannot find current version of file #%d', $item_id)));
        }

        $can_write = (! $this->filename_pattern_retriever->getPattern($project_id)->isEnforced()) &&
                     $docman_permissions_manager->userCanWrite($user, $item_id) &&
                     (! $this->lock_factory->itemIsLocked($item)) &&
                     (! $this->approval_table_retriever->hasApprovalTable($item));

        return Result::ok(
            new DocmanFileLastVersion($item, $version, $can_write)
        );
    }
}
