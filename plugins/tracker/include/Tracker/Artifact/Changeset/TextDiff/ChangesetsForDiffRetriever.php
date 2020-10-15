<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\TextDiff;

use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

class ChangesetsForDiffRetriever
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $field_factory;
    /**
     * @var \Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    public function __construct(
        \Tracker_Artifact_ChangesetFactory $changeset_factory,
        \Tracker_FormElementFactory $field_factory
    ) {
        $this->changeset_factory = $changeset_factory;
        $this->field_factory     = $field_factory;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function retrieveChangesets(Artifact $artifact, int $field_id, int $changeset_id): ChangesetsForDiff
    {
        $next_changeset = $this->changeset_factory->getChangeset($artifact, $changeset_id);
        if (! $next_changeset) {
            throw new NotFoundException(dgettext("tuleap-tracker", 'Changeset is not found.'));
        }

        $field = $this->field_factory->getFieldById($field_id);
        if (! $field) {
            throw new NotFoundException(dgettext("tuleap-tracker", 'Field not found.'));
        }

        if (! $field instanceof \Tracker_FormElement_Field_Text) {
            throw new ForbiddenException(dgettext("tuleap-tracker", 'Only text fields are supported for diff.'));
        }

        $previous_changeset = $artifact->getPreviousChangeset($next_changeset->getId());

        return new ChangesetsForDiff($next_changeset, $field, $previous_changeset);
    }
}
