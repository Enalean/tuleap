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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\SectionContentToBeCreatedArtifact;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\SectionContentToBeImported;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\SectionContentToBeCreatedFreetext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final readonly class SectionContentToBeCreated
{
    /**
     * @param Option<SectionContentToBeImported> $import
     * @param Option<SectionContentToBeCreatedFreetext> $freetext
     * @param Option<SectionContentToBeCreatedArtifact> $artifact
     */
    private function __construct(
        private Option $import,
        private Option $freetext,
        private Option $artifact,
    ) {
    }

    public static function fromImportedArtifact(int $import, Level $level): self
    {
        return new self(
            Option::fromValue(new SectionContentToBeImported($import, $level)),
            Option::nothing(SectionContentToBeCreatedFreetext::class),
            Option::nothing(SectionContentToBeCreatedArtifact::class),
        );
    }

    public static function fromFreetext(string $title, string $description, Level $level): self
    {
        return new self(
            Option::nothing(SectionContentToBeImported::class),
            Option::fromValue(
                new SectionContentToBeCreatedFreetext(
                    new FreetextContent($title, $description, $level),
                ),
            ),
            Option::nothing(SectionContentToBeCreatedArtifact::class),
        );
    }

    /**
     * @param list<int> $attachments
     */
    public static function fromArtifact(string $title, string $description, array $attachments, Level $level): self
    {
        return new self(
            Option::nothing(SectionContentToBeImported::class),
            Option::nothing(SectionContentToBeCreatedFreetext::class),
            Option::fromValue(
                new SectionContentToBeCreatedArtifact(
                    new ArtifactContent($title, $description, $attachments, $level),
                ),
            ),
        );
    }

    /**
     * @template TImportedArtifactReturn
     * @template TFreetextReturn
     * @template TArtifactReturn
     * @psalm-param callable(SectionContentToBeImported): (Ok<TImportedArtifactReturn>|Err<Fault>) $imported_artifact_callback
     * @psalm-param callable(SectionContentToBeCreatedFreetext): (Ok<TFreetextReturn>|Err<Fault>) $freetext_callback
     * @psalm-param callable(SectionContentToBeCreatedArtifact): (Ok<TArtifactReturn>|Err<Fault>) $artifact_callback
     * @return Ok<TImportedArtifactReturn>|Ok<TFreetextReturn>|Ok<TArtifactReturn>|Err<Fault>
     */
    public function apply(
        callable $imported_artifact_callback,
        callable $freetext_callback,
        callable $artifact_callback,
    ): Ok|Err {
        return $this->import->match(
            fn ($import) => $imported_artifact_callback($import),
            fn () => $this->freetext->match(
                fn ($freetext) => $freetext_callback($freetext),
                fn () => $this->artifact->match(
                    fn ($artifact) => $artifact_callback($artifact),
                    fn () => Result::err(UnknownSectionContentFault::build()),
                ),
            ),
        );
    }
}
