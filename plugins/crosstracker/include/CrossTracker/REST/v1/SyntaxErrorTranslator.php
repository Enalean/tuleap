<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;

final class SyntaxErrorTranslator
{
    private function __construct()
    {
    }

    public static function fromSyntaxError(SyntaxError $error): array
    {
        $expected = implode(', ', array_map(
            static fn(array $expection) => $expection['description'] === 'end of input'
                ? dgettext('tuleap-crosstracker', 'end of input')
                : $expection['description'],
            $error->expected,
        ));

        return [
            'error_message'      => sprintf('Error while parsing the query. Expected to find one of the following: %s', $expected),
            'i18n_error_message' => sprintf(
                dgettext('tuleap-crosstracker', 'Error while parsing the query. Expected to find one of the following: %s'),
                $expected,
            ),
            'details'            => [
                'line'   => $error->grammarLine,
                'column' => $error->grammarColumn,
            ],
        ];
    }
}
