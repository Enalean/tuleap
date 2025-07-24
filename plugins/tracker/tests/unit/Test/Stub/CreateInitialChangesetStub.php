<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateInitialChangeset;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class CreateInitialChangesetStub implements CreateInitialChangeset
{
    private int $nb_calls = 0;

    private function __construct(private bool $expects_changeset_creation)
    {
    }

    public static function withNoChangesetCreationExpected(): self
    {
        return new self(false);
    }

    public static function withChangesetCreationExpected(): self
    {
        return new self(true);
    }

    #[\Override]
    public function create(Artifact $artifact, array $fields_data, \PFUser $submitter, int $submitted_on, CreatedFileURLMapping $url_mapping, TrackerImportConfig $import_config, ChangesetValidationContext $changeset_validation_context,): ?int
    {
        $this->nb_calls++;

        if ($this->expects_changeset_creation) {
            return 1;
        }

        throw new \Exception('CreateInitialChangeset::create has been called while it was not expected to be called');
    }

    public function getCallCount(): int
    {
        return $this->nb_calls;
    }
}
