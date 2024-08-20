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

namespace Tuleap\Tracker\Report\Query\Advanced;

use Exception;

final class FromIsInvalidException extends Exception
{
    private string $i18n_message;

    /**
     * @param list<string> $messages
     */
    public function __construct(array $messages)
    {
        $messages_list = implode("', '", $messages);
        parent::__construct(sprintf("'FROM' part of the query is invalid, it contains some error: '%s'", $messages_list));
        $this->i18n_message = sprintf(dngettext(
            'tuleap-tracker',
            "The 'FROM' part of the query is invalid, it contains an error: '%s'",
            "The 'FROM' part of the query is invalid, it contains some error: '%s'",
            count($messages),
        ), $messages_list);
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
