<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document\Section\Versions;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class VersionBelongsToASectionOfTheArtidocChecker
{
    public function __construct(private CheckVersionExistsForArtidoc $check_version_exists_for_artidoc)
    {
    }

    /**
     * @return Ok<null>|Err<VersionDoesNotBelongToASectionOfCurrentArtidocFault>
     */
    public function checkVersionBelongsToAnArtidocSection(ArtidocWithContext $artidoc_with_context, int $version_id): Ok|Err
    {
        $artidoc_id = $artidoc_with_context->document->getId();
        if ($this->check_version_exists_for_artidoc->doesVersionBelongToASectionOfArtidoc($artidoc_id, $version_id)) {
            return Result::ok(null);
        }

        return Result::Err(VersionDoesNotBelongToASectionOfCurrentArtidocFault::build($artidoc_id, $version_id));
    }
}
