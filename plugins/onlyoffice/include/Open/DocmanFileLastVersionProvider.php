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
    ) {
    }

    /**
     * @psalm-return Ok<\Docman_Version>|Err<Fault>
     */
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

        $docman_permissions_manager = \Docman_PermissionsManager::instance($item->getGroupId());
        if (! $docman_permissions_manager->userCanAccess($user, $item->getId())) {
            return Result::err(Fault::fromMessage(sprintf('User #%d cannot access file #%d', $user->getId(), $item_id)));
        }

        $version = $this->version_factory->getCurrentVersionForItem($item);
        if ($version === null) {
            return Result::err(Fault::fromMessage(sprintf('Cannot find current version of file #%d', $item_id)));
        }

        return Result::ok($version);
    }
}
