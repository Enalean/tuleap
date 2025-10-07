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

namespace Tuleap\Artidoc\Stubs\Document;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchAllArtifactSections;

final class SearchAllArtifactSectionsStub implements SearchAllArtifactSections
{
    /** @var array<int, list<RetrievedSection>> */
    private array $sections_of_artidoc = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @no-named-arguments
     */
    public function withSections(ArtidocWithContext $artidoc, RetrievedSection ...$sections): self
    {
        $this->sections_of_artidoc[$artidoc->document->getId()] = $sections;
        return $this;
    }

    #[\Override]
    public function searchAllArtifactSectionsOfDocument(ArtidocWithContext $artidoc): array
    {
        return $this->sections_of_artidoc[$artidoc->document->getId()] ?? [];
    }
}
