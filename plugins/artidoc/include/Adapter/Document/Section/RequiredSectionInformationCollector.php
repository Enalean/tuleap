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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\BuildRequiredArtifactInformation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\RequiredArtifactInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class RequiredSectionInformationCollector implements CollectRequiredSectionInformation
{
    /**
     * @var array<int, RequiredArtifactInformation>
     */
    private array $collected = [];

    public function __construct(
        private readonly \PFUser $current_user,
        private readonly BuildRequiredArtifactInformation $required_artifact_information_builder,
    ) {
    }

    public function collectRequiredSectionInformation(ArtidocWithContext $artidoc, int $artifact_id): Ok|Err
    {
        return $this->required_artifact_information_builder
            ->getRequiredArtifactInformation($artidoc, $artifact_id, $this->current_user)
            ->map(function (RequiredArtifactInformation $info) use ($artifact_id) {
                $this->collected[$artifact_id] = $info;

                return null;
            });
    }

    /**
     * @return Ok<RequiredArtifactInformation>|Err<Fault>
     */
    public function getCollectedRequiredSectionInformation(int $artifact_id): Ok|Err
    {
        return isset($this->collected[$artifact_id])
            ? Result::ok($this->collected[$artifact_id])
            : Result::err(Fault::fromMessage('Unable to retrieve required section information for creation.'));
    }
}
