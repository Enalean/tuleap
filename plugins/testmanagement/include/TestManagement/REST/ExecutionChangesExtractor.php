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

namespace Tuleap\TestManagement\REST;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

class ExecutionChangesExtractor
{
    public const FIELD_RESULTS = 'results';
    public const FIELD_STATUS  = 'status';
    public const FIELD_TIME    = 'time';

    /**
     * @var FormattedChangesetValueForFileFieldRetriever
     */
    private $formatted_changeset_value_for_file_field_retriever;
    /**
     * @var FormattedChangesetValueForIntFieldRetriever
     */
    private $formatted_changeset_value_for_int_field_retriever;
    /**
     * @var FormattedChangesetValueForTextFieldRetriever
     */
    private $formatted_changeset_value_for_text_field_retriever;
    /**
     * @var FormattedChangesetValueForListFieldRetriever
     */
    private $formatted_changeset_value_for_list_field_retriever;

    public function __construct(
        FormattedChangesetValueForFileFieldRetriever $formatted_changeset_value_for_file_field_retriever,
        FormattedChangesetValueForIntFieldRetriever $formatted_changeset_value_for_int_field_retriever,
        FormattedChangesetValueForTextFieldRetriever $formatted_changeset_value_for_text_field_retriever,
        FormattedChangesetValueForListFieldRetriever $formatted_changeset_value_for_list_field_retriever
    ) {
        $this->formatted_changeset_value_for_file_field_retriever = $formatted_changeset_value_for_file_field_retriever;
        $this->formatted_changeset_value_for_int_field_retriever  = $formatted_changeset_value_for_int_field_retriever;
        $this->formatted_changeset_value_for_text_field_retriever = $formatted_changeset_value_for_text_field_retriever;
        $this->formatted_changeset_value_for_list_field_retriever = $formatted_changeset_value_for_list_field_retriever;
    }

    /**
     * @throws RestException
     */
    public function getChanges(
        string $status,
        array $uploaded_file_ids,
        int $time,
        string $results,
        Artifact $artifact,
        PFUser $user
    ): array {
        $changes = [];

        $status_value = $this->formatted_changeset_value_for_list_field_retriever
            ->getFormattedChangesetValueForFieldList(self::FIELD_STATUS, $status, $artifact, $user);
        if ($status_value) {
            $changes[] = $status_value;
        }

        $result_value = $this->formatted_changeset_value_for_text_field_retriever
            ->getFormattedChangesetValueForFieldText(
                self::FIELD_RESULTS,
                $results,
                $artifact,
                $user
            );

        if ($result_value) {
            $changes[] = $result_value;
        }

        if ($uploaded_file_ids !== []) {
            $changes[] = $this->formatted_changeset_value_for_file_field_retriever
                ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $artifact, $user);
        }

        if ($time !== 0) {
            $time_value = $this->formatted_changeset_value_for_int_field_retriever
                ->getFormattedChangesetValueForFieldInt(self::FIELD_TIME, $time, $artifact, $user);
            if ($time_value) {
                $changes[] = $time_value;
            }
        }

        return $changes;
    }
}
