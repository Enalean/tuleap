<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Artifact;

use Tracker_ArtifactFactory;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactRetriever
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @throws ArtifactNotFoundException
     */
    public function retrieveArtifactById(WebhookTuleapReference $reference): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($reference->getId());

        if (! $artifact) {
            throw new ArtifactNotFoundException();
        }
        return $artifact;
    }
}
