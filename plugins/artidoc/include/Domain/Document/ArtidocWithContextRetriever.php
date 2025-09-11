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

namespace Tuleap\Artidoc\Domain\Document;

use Override;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;

final class ArtidocWithContextRetriever implements RetrieveArtidocWithContext
{
    private const string USER_CAN_READ  = 'user-can-read';
    private const string USER_CAN_WRITE = 'user-can-write';

    public function __construct(
        private RetrieveArtidoc $artidoc_retriever,
        private CheckCurrentUserHasArtidocPermissions $permissions,
        private DecorateArtidocWithContext $artidoc_with_context_decorator,
    ) {
    }

    #[Override]
    public function retrieveArtidocUserCanRead(int $id): Ok|Err
    {
        return $this->retrieveArtidoc($id, self::USER_CAN_READ);
    }

    #[Override]
    public function retrieveArtidocUserCanWrite(int $id): Ok|Err
    {
        return $this->retrieveArtidoc($id, self::USER_CAN_WRITE);
    }

    /**
     * @param self::USER_CAN_* $perms
     */
    private function retrieveArtidoc(int $id, string $perms): Ok|Err
    {
        return $this->artidoc_retriever
            ->retrieveArtidoc($id)
            ->andThen(fn (Artidoc $artidoc) => match ($perms) {
                self::USER_CAN_READ  => $this->permissions->checkUserCanRead($artidoc),
                self::USER_CAN_WRITE => $this->permissions->checkUserCanWrite($artidoc),
            })
            ->andThen(fn (Artidoc $artidoc) => $this->artidoc_with_context_decorator->decorate($artidoc));
    }
}
