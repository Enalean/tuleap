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

namespace Tuleap\Artidoc\Domain\Document\Section\Artifact;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

interface CreateArtifactContent
{
    /**
     * @return Ok<int>|Err<Fault>
     */
    public function createArtifact(ArtidocWithContext $artidoc, ArtifactContent $content): Ok|Err;
}
