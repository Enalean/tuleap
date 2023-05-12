<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Link;

use PFUser;
use Tracker_Exception;
use Tracker_NoChangeException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;

interface HandleUpdateArtifact
{
    /**
     * @throws FieldValidationException
     * @throws \Tracker_NoChangeException
     * @throws \Tracker_Exception
     * @return Ok<null>|Err<Fault>
     */
    public function removeReverseLinks(
        Artifact $current_artifact,
        PFUser $submitter,
        CollectionOfReverseLinks $removed_reverse_links,
    ): Ok|Err;

    /**
     * @throws FieldValidationException
     * @throws \Tracker_NoChangeException
     * @throws \Tracker_Exception
     * @return Ok<null>|Err<Fault>
     */
    public function updateTypeAndAddReverseLinks(
        Artifact $current_artifact,
        PFUser $submitter,
        CollectionOfReverseLinks $added_reverse_link,
        CollectionOfReverseLinks $updated_reverse_link_type,
    ): Ok|Err;

    /**
     * @throws FieldValidationException
     * @throws Tracker_NoChangeException
     * @throws Tracker_Exception
     */
    public function updateForwardLinks(
        Artifact $current_artifact,
        PFUser $submitter,
        ChangesetValuesContainer $changeset_values_container,
        ?NewChangesetCommentRepresentation $comment = null,
    ): void;
}
