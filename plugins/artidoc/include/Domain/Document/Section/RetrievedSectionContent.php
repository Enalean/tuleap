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

use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final readonly class RetrievedSectionContent
{
    /**
     * @param Option<int> $artifact_id
     * @param Option<RetrievedSectionContentFreetext> $freetext
     */
    private function __construct(
        private Option $artifact_id,
        private Option $freetext,
    ) {
    }

    public static function fromArtifact(int $artifact_id): self
    {
        return new self(
            Option::fromValue($artifact_id),
            Option::nothing(RetrievedSectionContentFreetext::class),
        );
    }

    public static function fromFreetext(FreetextIdentifier $id, string $title, string $description, Level $level): self
    {
        return new self(
            Option::nothing(\Psl\Type\int()),
            Option::fromValue(
                new RetrievedSectionContentFreetext(
                    $id,
                    new FreetextContent($title, $description, $level),
                ),
            ),
        );
    }

    /**
     * @template TArtifactReturn
     * @template TFreetextReturn
     * @psalm-param callable(int): (Ok<TArtifactReturn>|Err<Fault>) $artifact_callback
     * @psalm-param callable(RetrievedSectionContentFreetext): (Ok<TFreetextReturn>|Err<Fault>)    $freetext_callback
     * @return Ok<TArtifactReturn>|Ok<TFreetextReturn>|Err<Fault>
     */
    public function apply(callable $artifact_callback, callable $freetext_callback): Ok|Err
    {
        return $this->artifact_id->match(
            fn ($artifact_id) => $artifact_callback($artifact_id),
            fn () => $this->freetext->match(
                fn ($freetext) => $freetext_callback($freetext),
                fn () => Result::err(UnknownSectionContentFault::build())
            ),
        );
    }
}
