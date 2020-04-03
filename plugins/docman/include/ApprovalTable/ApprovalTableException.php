<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\ApprovalTable;

use Exception;

class ApprovalTableException extends Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message)
    {
        parent::__construct($message);
        $this->i18n_message = $i18n_message;
    }


    public static function approvalTableActionIsMandatory(string $title): self
    {
        return new self(
            'approval_table_action is required',
            sprintf(
                dgettext(
                    'tuleap-docman',
                    '%s has an approval table, you must provide "approval_table_action" parameter to choose the option creation of table (possible values: copy,reset,empty).'
                ),
                $title
            )
        );
    }

    public static function approvalTableActionShouldNotBeProvided(string $title): self
    {
        return new self(
            'approval_table_action should not be provided',
            sprintf(
                dgettext(
                    'tuleap-docman',
                    '%s does not have an approval table. The parameter "approval_table_action" must not be present.'
                ),
                $title
            )
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
