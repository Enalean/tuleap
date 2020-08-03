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

use HTTPRequest;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class TextDiffRetriever implements DispatchableWithRequest
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ChangesetsForDiffRetriever
     */
    private $changesets_for_diff_retriever;
    /**
     * @var DiffProcessor
     */
    private $diff_processor;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        ChangesetsForDiffRetriever $changesets_for_diff_retriever,
        DiffProcessor $diff_processor
    ) {
        $this->artifact_factory              = $artifact_factory;
        $this->changesets_for_diff_retriever = $changesets_for_diff_retriever;
        $this->diff_processor                = $diff_processor;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $changeset_id = (int) $variables['changeset_id'];
        $artifact_id  = (int) $variables['artifact_id'];
        $field_id     = (int) $variables['field_id'];
        $format       = (string) $variables['format'];

        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $artifact) {
            throw new NotFoundException(dgettext("tuleap-tracker", 'Artifact is not found or not accessible by user.'));
        }

        $user = $request->getCurrentUser();
        if (! $artifact->userCanView($user)) {
            throw new NotFoundException(dgettext("tuleap-tracker", 'Artifact is not found or not accessible by user.'));
        }

        $changesets_for_diff = $this->changesets_for_diff_retriever->retrieveChangesets(
            $artifact,
            $field_id,
            $changeset_id
        );

        $field                = $changesets_for_diff->getFieldText();
        $next_changeset       = $changesets_for_diff->getNextChangeset();
        $next_changeset_value = $next_changeset->getValue($field);

        if (! $next_changeset_value instanceof \Tracker_Artifact_ChangesetValue_Text) {
            throw new \LogicException('Only text follow_up are supported.');
        }

        $previous_changeset = $changesets_for_diff->getPreviousChangeset();
        if (! $previous_changeset) {
            $layout->sendJSON("");
            return;
        }

        $previous_changeset_value = $previous_changeset->getValue($field);

        if (! $previous_changeset_value || ! $previous_changeset_value instanceof Tracker_Artifact_ChangesetValue_Text) {
            $layout->sendJSON("");
            return;
        }

        $layout->sendJSON(
            $this->diff_processor->processDiff($next_changeset_value, $previous_changeset_value, $format)
        );
    }
}
