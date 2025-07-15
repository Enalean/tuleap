<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Logs;

use Tuleap\Date\TlpRelativeDatePresenterBuilder;

final class LogLinePresenterBuilder
{
    public function __construct(
        private readonly TlpRelativeDatePresenterBuilder $tlp_relative_date_presenter_builder,
    ) {
    }

    public function getPresenter(FunctionLogLineWithArtifact $log, \PFUser $user): LogLinePresenter
    {
        $fake_changeset_for_url = new \Tracker_Artifact_Changeset($log->log_line->changeset_id, $log->artifact, '', '', '');

        $execution_date = (new \DateTimeImmutable())->setTimestamp($log->log_line->execution_date);

        return new LogLinePresenter(
            $log->id,
            $log->log_line->status === FunctionLogLineStatus::ERROR,
            $this->tlp_relative_date_presenter_builder->getTlpRelativeDatePresenterInBlockContext(
                $execution_date,
                $user,
            ),
            $this->tlp_relative_date_presenter_builder->getTlpRelativeDatePresenterInInlineContext(
                $execution_date,
                $user,
            ),
            match ((int) $log->artifact->getFirstChangeset()->getId()) {
                $log->log_line->changeset_id => dgettext('tuleap-tracker_functions', 'Creation'),
                default => dgettext('tuleap-tracker_functions', 'Update'),
            },
            $fake_changeset_for_url->getUri(),
            $log->artifact->getXRef(),
            $log->artifact->getTitle() ?? '',
            $log->artifact->getTracker()->getColor()->value,
            PayloadDownloaderController::buildURL($log->log_line->changeset_id),
            $log->log_line->error_message,
        );
    }
}
