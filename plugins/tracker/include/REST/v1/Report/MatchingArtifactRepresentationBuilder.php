<?php
/**
 * Copyright (c) Enalean, 2022 — Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Report;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Report;
use Tracker_Report_Renderer_Table;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Renderer\Table\TableRendererForReportRetriever;
use Tuleap\Tracker\Report\Renderer\Table\UsedFieldsRetriever;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;
use Tuleap\Tracker\Semantic\Status\StatusColorForChangesetProvider;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

class MatchingArtifactRepresentationBuilder
{
    public function __construct(
        private ReportArtifactFactory $report_artifact_factory,
        private TableRendererForReportRetriever $table_renderer_retriever,
        private UsedFieldsRetriever $used_fields_retriever,
        private StatusColorForChangesetProvider $status_value_for_changeset_provider,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    /**
     * @throw RestException
     */
    public function buildMatchingArtifactRepresentationCollection(
        PFUser $current_user,
        Tracker_Report $report,
        ?int $table_renderer_id,
        int $limit,
        int $offset,
    ): MatchingArtifactRepresentationCollection {
        $renderer_table        = $this->getTableReportRendererForReport($report, $table_renderer_id);
        $renderer_table_fields = $this->used_fields_retriever->getUsedFieldsInRendererUserCanSee(
            $current_user,
            $renderer_table,
        );

        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReport(
            $report,
            $limit,
            $offset
        );

        $minimal_tracker_representation = MinimalTrackerRepresentation::build($report->getTracker());

        $artifact_representations = [];
        foreach ($artifact_collection->getArtifacts() as $matching_artifact) {
            if (! $matching_artifact->userCanView($current_user)) {
                continue;
            }

            $last_changeset = $matching_artifact->getLastChangeset();
            if ($last_changeset === null) {
                continue;
            }

            $artifact_representations[] = ArtifactRepresentation::build(
                $current_user,
                $matching_artifact,
                $this->getRESTFieldValues(
                    $current_user,
                    $last_changeset,
                    $renderer_table_fields
                ),
                null,
                $minimal_tracker_representation,
                StatusValueRepresentation::buildFromValues(
                    $matching_artifact->getStatus(),
                    $this->status_value_for_changeset_provider->provideColor($last_changeset, $matching_artifact->getTracker(), $current_user)
                ),
                $this->provide_user_avatar_url,
            );
        }

        return new MatchingArtifactRepresentationCollection(
            $artifact_representations,
            $artifact_collection->getTotalSize()
        );
    }

    /**
     * @throw RestException
     */
    private function getTableReportRendererForReport(Tracker_Report $report, ?int $table_renderer_id): Tracker_Report_Renderer_Table
    {
        $table_renderers = $this->table_renderer_retriever->getTableReportRendererForReport($report);

        if (count($table_renderers) === 0) {
            throw new RestException(
                400,
                'The report does not have a table renderer'
            );
        }

        foreach ($table_renderers as $table_renderer) {
            if ($table_renderer->getId() === $table_renderer_id) {
                return $table_renderer;
            }
        }

        if (count($table_renderers) > 1) {
            throw new RestException(
                400,
                'The report has more than one table renderer'
            );
        }

        if ($table_renderer_id === null) {
            return $table_renderers[0];
        }

        throw new RestException(
            400,
            'The requested table report renderer has not been found in the report'
        );
    }

    /**
     * @param TrackerField[] $renderer_table_fields
     */
    private function getRESTFieldValues(
        PFUser $current_user,
        Tracker_Artifact_Changeset $last_changeset,
        array $renderer_table_fields,
    ): array {
        $rest_field_values = [];
        foreach ($renderer_table_fields as $field) {
            if ($field instanceof ArtifactLinkField) {
                //artifact link fields are skipped for this representation
                continue;
            }

            $rest_field_values[] = $field->getRESTValue(
                $current_user,
                $last_changeset
            );
        }

        return array_values(
            array_filter(
                $rest_field_values
            )
        );
    }
}
