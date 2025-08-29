<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document;

use Override;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Domain\Document\CheckCurrentUserHasArtidocPermissions;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CurrentUserHasArtidocPermissionsChecker implements CheckCurrentUserHasArtidocPermissions
{
    private function __construct(private \PFUser $current_user)
    {
    }

    public static function withCurrentUser(\PFUser $current_user): self
    {
        return new self($current_user);
    }

    #[Override]
    public function checkUserCanRead(Artidoc $artidoc): Ok|Err
    {
        if (! $this->getPermissions($artidoc)->userCanRead($this->current_user, $artidoc->getId())) {
            return Result::err(Fault::fromMessage('User cannot read document'));
        }

        return Result::ok($artidoc);
    }

    #[Override]
    public function checkUserCanWrite(Artidoc $artidoc): Ok|Err
    {
        if (! $this->getPermissions($artidoc)->userCanWrite($this->current_user, $artidoc->getId())) {
            return Result::err(UserCannotWriteDocumentFault::build());
        }

        return Result::ok($artidoc);
    }

    private function getPermissions(Artidoc $artidoc): \Docman_PermissionsManager
    {
        return \Docman_PermissionsManager::instance($artidoc->getProjectId());
    }
}
