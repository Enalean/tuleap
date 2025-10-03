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

namespace Tuleap\Artidoc\Builders;

use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;

final class RetrievedSectionBuilder
{
    private ?int $artifact_id                = null;
    private ?FreetextIdentifier $freetext_id = null;
    private int $rank                        = 0;
    private Level $level                     = Level::One;

    private function __construct(
        private readonly int $artidoc_id,
        private readonly SectionIdentifier $id,
    ) {
    }

    public static function anArtifactSection(int $artidoc_id, int $artifact_id): self
    {
        $section              = new self($artidoc_id, SectionIdentifierStub::create());
        $section->artifact_id = $artifact_id;
        return $section;
    }

    public static function aFreeTextSection(int $artidoc_id, FreetextIdentifier $freetext_id): self
    {
        $section              = new self($artidoc_id, SectionIdentifierStub::create());
        $section->freetext_id = $freetext_id;
        return $section;
    }

    public function withRank(int $rank): self
    {
        $this->rank = $rank;
        return $this;
    }

    public function withLevel(Level $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function build(): RetrievedSection
    {
        if ($this->freetext_id !== null) {
            return RetrievedSection::fromFreetext([
                'freetext_title'       => 'Requirements',
                'freetext_description' => 'Lorem ipsum',
                'freetext_id'          => $this->freetext_id,
                'id'                   => $this->id,
                'item_id'              => $this->artidoc_id,
                'rank'                 => $this->rank,
                'level'                => $this->level->value,
            ]);
        }
        assert($this->artifact_id !== null);

        return RetrievedSection::fromArtifact([
            'id' => $this->id,
            'item_id' => $this->artidoc_id,
            'artifact_id' => $this->artifact_id,
            'rank' => $this->rank,
            'level' => $this->level->value,
        ]);
    }
}
