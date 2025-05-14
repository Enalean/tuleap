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

namespace Tuleap\Artidoc\Stubs\REST\v1\ArtifactSection;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\REST\v1\ArtifactSection\BuildRequiredArtifactInformation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\RequiredArtifactInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class BuildRequiredArtifactInformationStub implements BuildRequiredArtifactInformation
{
    /**
     * @param array<int, RequiredArtifactInformation>|null $info
     */
    private function __construct(private ?array $info)
    {
    }

    /**
     * @param array<int, RequiredArtifactInformation> $info
     */
    public static function withRequiredArtifactInformation(array $info): self
    {
        return new self($info);
    }

    public static function withoutRequiredArtifactInformation(): self
    {
        return new self([]);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public function getRequiredArtifactInformation(ArtidocWithContext $artidoc, int $artifact_id, \PFUser $user): Ok|Err
    {
        if ($this->info === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        if (isset($this->info[$artifact_id])) {
            return Result::ok($this->info[$artifact_id]);
        }

        return Result::err(Fault::fromMessage('You cannot see the artifact'));
    }
}
