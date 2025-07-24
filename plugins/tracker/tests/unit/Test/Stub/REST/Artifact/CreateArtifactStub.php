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

namespace Tuleap\Tracker\Test\Stub\REST\Artifact;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\CreateArtifact;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class CreateArtifactStub implements CreateArtifact
{
    /**
     * @var ArtifactValuesRepresentation[]|null
     */
    private ?array $payload = null;

    private function __construct(private readonly bool $callable, private readonly ?Artifact $artifact)
    {
    }

    public static function withCreatedArtifact(Artifact $artifact): self
    {
        return new self(true, $artifact);
    }

    public static function withException(): self
    {
        return new self(true, null);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(false, null);
    }

    #[\Override]
    public function create(
        PFUser $submitter,
        TrackerReference $tracker_reference,
        array $values,
        bool $should_visit_be_recorded,
    ): ArtifactReference {
        if ($this->callable === false) {
            throw new \Exception('Unexpected call to method ' . __METHOD__);
        }

        $this->payload = $values;

        if ($this->artifact === null) {
            throw new RestException(400, 'Invalid format for field');
        }

        return ArtifactReference::build($this->artifact);
    }

    public function isCalled(): bool
    {
        return $this->payload !== null;
    }

    /**
     * @return ArtifactValuesRepresentation[]|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }
}
