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

use Codendi_Diff;
use Codendi_UnifiedDiffFormatter;

class DiffProcessor
{
    /**
     * @var Codendi_UnifiedDiffFormatter
     */
    private $diff_formatter;

    public function __construct(Codendi_UnifiedDiffFormatter $diff_formatter)
    {
        $this->diff_formatter = $diff_formatter;
    }

    public function processDiff(
        \Tracker_Artifact_ChangesetValue_Text $next_changeset_value,
        \Tracker_Artifact_ChangesetValue_Text $previous_changeset_value,
        string $format
    ): string {
        switch ($format) {
            case 'text':
                $previous_value = explode(PHP_EOL, $previous_changeset_value->getText());
                $next_value     = explode(PHP_EOL, $next_changeset_value->getText());

                $diff = new Codendi_Diff($previous_value, $next_value);

                return PHP_EOL . $this->diff_formatter->format($diff);
            case 'strip-html':
                $previous_value = explode(PHP_EOL, $previous_changeset_value->getValue());
                $next_value     = explode(PHP_EOL, $next_changeset_value->getValue());
                return $next_changeset_value->getFormattedDiff($previous_value, $next_value, CODENDI_PURIFIER_STRIP_HTML);
            case 'html':
                $previous_value = explode(PHP_EOL, $previous_changeset_value->getText());
                $next_value     = explode(PHP_EOL, $next_changeset_value->getText());
                return $next_changeset_value->getFormattedDiff($previous_value, $next_value, CODENDI_PURIFIER_CONVERT_HTML);
            default:
                return "";
        }
    }
}
