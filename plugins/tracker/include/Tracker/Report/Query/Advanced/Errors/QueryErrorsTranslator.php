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

namespace Tuleap\Tracker\Report\Query\Advanced\Errors;

use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectException;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\Tracker\Report\Query\Advanced\SyntaxNotSupportedException;

final readonly class QueryErrorsTranslator
{
    public static function translateException(
        SyntaxNotSupportedException|LimitSizeIsExceededException|InvalidSelectException|SelectablesMustBeUniqueException|SelectLimitExceededException|MissingFromException $exception,
    ): string {
        return match ($exception::class) {
            SyntaxNotSupportedException::class => dgettext(
                'tuleap-tracker',
                "Usage of 'SELECT', 'FROM', 'ORDER BY' and 'WHERE' is not allowed in the Tracker report context"
            ),
            LimitSizeIsExceededException::class => dgettext(
                'tuleap-tracker',
                'The query is considered too complex to be executed by the server. Please simplify it (for example remove comparisons) to continue.'
            ),
            InvalidSelectException::class => dgettext(
                'tuleap-tracker',
                '"SELECT" keyword is not allowed in default mode'
            ),
            SelectablesMustBeUniqueException::class => dgettext(
                'tuleap-tracker',
                'Selection on same field multiple times is not allowed'
            ),
            SelectLimitExceededException::class => sprintf(
                dngettext(
                    'tuleap-tracker',
                    'You can select at most %1$d columns, but you have selected %2$d column.',
                    'You can select at most %1$d columns, but you have selected %2$d columns.',
                    $exception->number_of_columns_selected,
                ),
                $exception->limit,
                $exception->number_of_columns_selected
            ),
            MissingFromException::class => dgettext(
                'tuleap-tracker',
                '"FROM" keyword is missing from your query, it is mandatory in expert mode'
            ),
        };
    }
}
