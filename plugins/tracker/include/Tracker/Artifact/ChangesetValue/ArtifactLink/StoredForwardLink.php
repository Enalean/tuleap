<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Tracker\Artifact\RetrieveArtifact;

/**
 * I hold a link retrieved from database storage
 * @psalm-immutable
 */
final class StoredForwardLink implements ForwardLink
{
    private function __construct(private int $id, private string $type)
    {
    }

    /**
     * @param array{artifact_id: int, nature: string|null} $row
     */
    public static function fromRow(RetrieveArtifact $artifact_retriever, \PFUser $user, array $row): ?self
    {
        $artifact = $artifact_retriever->getArtifactById($row['artifact_id']);
        if (! $artifact || ! $artifact->userCanView($user)) {
            return null;
        }
        return new self($artifact->getId(), $row['nature'] ?? \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::NO_TYPE);
    }

    public function getTargetArtifactId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
