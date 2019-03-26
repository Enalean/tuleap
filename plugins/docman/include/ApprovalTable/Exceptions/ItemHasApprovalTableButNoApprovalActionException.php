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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable\Exceptions;

class ItemHasApprovalTableButNoApprovalActionException extends \Exception
{
    public function __construct($item_title)
    {
        parent::__construct(
            sprintf(
                dgettext(
                    'tuleap-docman',
                    '%s has an approval table, you must provide "approval_table_action" parameter to choose the option creation of table (possible values: copy,reset,empty).'
                ),
                $item_title
            )
        );
    }
}
