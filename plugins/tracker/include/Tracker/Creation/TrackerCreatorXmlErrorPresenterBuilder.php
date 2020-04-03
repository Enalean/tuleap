<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation;

use XML_ParseError;

class TrackerCreatorXmlErrorPresenterBuilder
{
    /**
     * protected for testing purpose
     */
    public function buildErrorLineDiff(array $xml_file, array $errors): TrackerCreatorXmlErrorPresenter
    {
        $lines = [];
        foreach ($xml_file as $number => $line) {
            $next_line                      = (int) $number + 1;
            $lines[$number]['line_id']      = $next_line;
            $lines[$number]['line_content'] = $line;
            $lines[$number]['has_error']    = false;

            if (! isset($errors[$next_line])) {
                continue;
            }

            foreach ($errors[$next_line] as $column => $error) {
                $lines[$number]['has_error'] = true;
                $error_message               = $this->getErrorMessagesForLine($error);

                if (count($error_message) > 0) {
                    $lines[$number]['error_message']    = implode(' - ', $error_message);
                    $lines[$number]['add_extra_spaces'] = $this->getAlwaysThereSpaces()
                        . $this->getSpacesAccordingToPreviousLineSize($column);
                }
            }
        }

        return new TrackerCreatorXmlErrorPresenter($lines);
    }

    private function getAlwaysThereSpaces(): string
    {
        return sprintf('%3s', '');
    }

    private function getSpacesAccordingToPreviousLineSize(int $column): string
    {
        return sprintf('%' . ($column - 1) . 's', '');
    }

    private function getErrorMessagesForLine(array $error): array
    {
        $error_message = [];
        foreach ($error as $parse_error) {
            $error_message[] = $parse_error->getMessage();
        }

        return $error_message;
    }

    public function buildErrors(array $parse_errors): array
    {
        $errors = [];
        foreach ($parse_errors as $error) {
            /** @var XML_ParseError $error */
            $errors[$error->getLine()][$error->getColumn()][] = $error;
        }

        return $errors;
    }
}
