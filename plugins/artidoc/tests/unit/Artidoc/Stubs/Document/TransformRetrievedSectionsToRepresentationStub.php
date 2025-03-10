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

namespace Tuleap\Artidoc\Stubs\Document;

use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\REST\v1\PaginatedArtidocSectionRepresentationCollection;
use Tuleap\Artidoc\REST\v1\TransformRetrievedSectionsToRepresentation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class TransformRetrievedSectionsToRepresentationStub implements TransformRetrievedSectionsToRepresentation
{
    /**
     * @param Ok<PaginatedArtidocSectionRepresentationCollection>|Err<Fault> $result
     */
    private function __construct(private Ok|Err|null $result)
    {
    }

    public static function withCollection(PaginatedArtidocSectionRepresentationCollection $collection): self
    {
        return new self(Result::ok($collection));
    }

    public static function withoutCollection(): self
    {
        return new self(Result::err(Fault::fromMessage('Cannot read')));
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public function getRepresentation(PaginatedRetrievedSections $retrieved_sections, \PFUser $user): Ok|Err
    {
        if ($this->result === null) {
            throw new \Exception('Unexpected call to getRepresentation()');
        }

        return $this->result;
    }
}
