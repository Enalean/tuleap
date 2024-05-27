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

final class SelectablesDoNotExistException extends Exception
{
    private string $i18n_message;

    /**
     * @param string[] $non_existing_selectables
     */
    public function __construct(array $non_existing_selectables)
    {
        $string_list = implode("', '", $non_existing_selectables);
        parent::__construct(sprintf("We cannot select on '%s', we don't know what they refer to. Please refer to the documentation for the allowed selectables.", $string_list));
        $this->i18n_message = sprintf(
            dngettext(
                'tuleap-tracker',
                "We cannot select on '%s', we don't know what it refers to. Please refer to the documentation for the allowed selectables.",
                "We cannot select on '%s', we don't know what they refer to. Please refer to the documentation for the allowed selectables.",
                count($non_existing_selectables)
            ),
            $string_list
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
