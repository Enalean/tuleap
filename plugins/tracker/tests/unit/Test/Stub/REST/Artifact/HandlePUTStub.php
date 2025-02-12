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
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\HandlePUT;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class HandlePUTStub implements HandlePUT
{
    /**
     * @var ArtifactValuesRepresentation[]|null
     */
    private ?array $payload = null;

    private function __construct(private bool $exception)
    {
    }

    public static function build(): self
    {
        return new self(false);
    }

    public static function buildWithException(): self
    {
        return new self(true);
    }

    public function handle(array $values, Artifact $artifact, \PFUser $submitter, ?NewChangesetCommentRepresentation $comment,): void
    {
        $this->payload = $values;

        if ($this->exception) {
            throw new RestException(400, 'Invalid format for field');
        }
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
