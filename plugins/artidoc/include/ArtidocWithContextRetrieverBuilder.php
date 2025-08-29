<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use Override;
use Tuleap\Artidoc\Adapter\Document\CurrentUserHasArtidocPermissionsChecker;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContextRetriever;
use Tuleap\Artidoc\Domain\Document\DecorateArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidoc;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;

final readonly class ArtidocWithContextRetrieverBuilder implements BuildArtidocWithContextRetriever
{
    public function __construct(
        private RetrieveArtidoc $artidoc_retriever,
        private DecorateArtidocWithContext $artidoc_with_context_decorator,
    ) {
    }

    #[Override]
    public function buildForUser(\PFUser $user): RetrieveArtidocWithContext
    {
        return new ArtidocWithContextRetriever(
            $this->artidoc_retriever,
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            $this->artidoc_with_context_decorator,
        );
    }
}
