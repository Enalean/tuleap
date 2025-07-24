<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Artifact\ChangesetValue;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\SaveChangesetValue;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class SaveChangesetValueStub implements SaveChangesetValue
{
    private int $count = 0;

    private function __construct(private readonly bool $save_succeed)
    {
    }

    public static function buildStoreField(): self
    {
        return new self(true);
    }

    public static function buildFail(): self
    {
        return new self(false);
    }

    #[\Override]
    public function saveNewChangesetForField(\Tracker_FormElement_Field $field, Artifact $artifact, ?\Tracker_Artifact_Changeset $previous_changeset, array $fields_data, \PFUser $submitter, int $changeset_id, \Workflow $workflow, CreatedFileURLMapping $url_mapping,): bool
    {
        $this->count++;
        return $this->save_succeed;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
