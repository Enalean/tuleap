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

namespace Tuleap\Artidoc\Stubs\Domain\Document\Section\Artifact;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\CreateArtifactContent;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CreateArtifactContentStub implements CreateArtifactContent
{
    /**
     * @param Ok<int>|Err<Fault>|null $result
     */
    private function __construct(private Ok|Err|null $result)
    {
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public static function withCreatedArtifactId(int $id): self
    {
        return new self(Result::ok($id));
    }

    public static function withFailedCreation(): self
    {
        return new self(Result::err(Fault::fromMessage('Artifact creation failed')));
    }

    #[\Override]
    public function createArtifact(ArtidocWithContext $artidoc, ArtifactContent $content): Ok|Err
    {
        if ($this->result === null) {
            throw new \Exception('Unexpected call to method ' .  __METHOD__);
        }

        return $this->result;
    }
}
