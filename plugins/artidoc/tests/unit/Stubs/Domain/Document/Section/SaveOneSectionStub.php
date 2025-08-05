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

namespace Tuleap\Artidoc\Stubs\Domain\Document\Section;

use Tuleap\Artidoc\Adapter\Document\Section\AlreadyExistingSectionWithSameArtifactFault;
use Tuleap\Artidoc\Adapter\Document\Section\UnableToFindSiblingSectionFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\SaveOneSection;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class SaveOneSectionStub implements SaveOneSection
{
    /**
     * @var array<int, ContentToInsert>
     */
    private array $saved_before = [];
    /**
     * @var array<int, ContentToInsert>
     */
    private array $saved_end = [];

    private const EXCEPTION_ALREADY_EXISTING_ARTIFACT = 'already-existing-artifact';
    private const EXCEPTION_NO_SIBLING_SECTION        = 'no-sibling-SECTION';

    /**
     * @param-param self::EXCEPTION_*|SectionIdentifierFactory $identifier_factory
     */
    private function __construct(
        private readonly string|SectionIdentifierFactory $identifier_factory,
        private readonly string $id,
    ) {
    }

    public static function withGeneratedSectionId(SectionIdentifierFactory $identifier_factory, string $id): self
    {
        return new self($identifier_factory, $id);
    }

    public static function withAlreadyExistingSectionWithSameArtifact(string $id): self
    {
        return new self(self::EXCEPTION_ALREADY_EXISTING_ARTIFACT, $id);
    }

    public static function withUnableToFindSiblingSection(string $id): self
    {
        return new self(self::EXCEPTION_NO_SIBLING_SECTION, $id);
    }

    public function isSaved(int $id): bool
    {
        return isset($this->saved_end[$id]) || isset($this->saved_before[$id]);
    }

    public function getSavedEndForId(int $id): ContentToInsert
    {
        return $this->saved_end[$id];
    }

    public function getSavedBeforeForId(int $id): ContentToInsert
    {
        return $this->saved_before[$id];
    }

    #[\Override]
    public function saveSectionAtTheEnd(ArtidocWithContext $artidoc, ContentToInsert $content): Ok|Err
    {
        return $this->getSectionIdentifierFactory()
            ->map(function (SectionIdentifierFactory $identifier_factory) use ($artidoc, $content) {
                $this->saved_end[$artidoc->document->getId()] = $content;

                return $identifier_factory->buildFromHexadecimalString($this->id);
            });
    }

    #[\Override]
    public function saveSectionBefore(ArtidocWithContext $artidoc, ContentToInsert $content, SectionIdentifier $sibling_section_id): Ok|Err
    {
        return $this->getSectionIdentifierFactory()
            ->map(function (SectionIdentifierFactory $identifier_factory) use ($artidoc, $content) {
                $this->saved_before[$artidoc->document->getId()] = $content;

                return $identifier_factory->buildFromHexadecimalString($this->id);
            });
    }

    /**
     * @return Ok<SectionIdentifierFactory>|Err<Fault>
     */
    private function getSectionIdentifierFactory(): Ok|Err
    {
        if (! $this->identifier_factory instanceof SectionIdentifierFactory) {
            return Result::err(match ($this->identifier_factory) {
                self::EXCEPTION_ALREADY_EXISTING_ARTIFACT => AlreadyExistingSectionWithSameArtifactFault::build(),
                self::EXCEPTION_NO_SIBLING_SECTION => UnableToFindSiblingSectionFault::build(),
            });
        }

        return Result::ok($this->identifier_factory);
    }
}
