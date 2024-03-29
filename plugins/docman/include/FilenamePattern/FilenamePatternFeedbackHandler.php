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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\FilenamePattern;

use Feedback;
use Tuleap\Docman\ResponseFeedbackWrapper;

final class FilenamePatternFeedbackHandler
{
    public function __construct(private FilenamePatternUpdater $filename_pattern_updater, private ResponseFeedbackWrapper $feedback)
    {
    }

    public function getFilenamePatternUpdateFeedback(int $project_id, FilenamePattern $filename_pattern): void
    {
        try {
            $this->filename_pattern_updater->updatePattern($project_id, $filename_pattern);
            $this->feedback->log(
                Feedback::INFO,
                dgettext('tuleap-docman', 'The pattern has been successfully updated.')
            );
        } catch (FilenamePatternException $exception) {
            $this->feedback->log(
                Feedback::ERROR,
                $exception->getMessage()
            );
        }
    }
}
