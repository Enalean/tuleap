<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

final class ChangesetValuePermissionsOnArtifactTestBuilder
{
    /**
     * @var array<int, string>
     */
    private array $allowed_user_group_keys = [];
    private bool $is_used                  = true;

    private function __construct(
        private readonly int $id,
        private readonly Tracker_Artifact_Changeset $changeset,
        private readonly PermissionsOnArtifactField $field,
    ) {
    }

    public static function aListOfPermissions(int $id, Tracker_Artifact_Changeset $changeset, PermissionsOnArtifactField $field): self
    {
        return new self($id, $changeset, $field);
    }

    /**
     * @param array<int, string> $values
     */
    public function withAllowedUserGroups(array $values): self
    {
        $this->allowed_user_group_keys = $values;
        return $this;
    }

    public function thatIsNotUsed(): self
    {
        $this->is_used = false;
        return $this;
    }

    public function build(): Tracker_Artifact_ChangesetValue_PermissionsOnArtifact
    {
        $value = new Tracker_Artifact_ChangesetValue_PermissionsOnArtifact(
            $this->id,
            $this->changeset,
            $this->field,
            true,
            $this->is_used,
            $this->allowed_user_group_keys,
        );

        $this->changeset->setFieldValue($this->field, $value);

        return $value;
    }
}
