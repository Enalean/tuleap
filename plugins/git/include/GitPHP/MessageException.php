<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

use Exception;

/**
 * GitPHP Message exception
 *
 * Custom exception for signalling display of a message to user
 *
 */

/**
 * Message Exception
 */
class MessageException extends Exception
{

    public $Error;

    public $StatusCode;

    /**
     * Constructor
     *
     * @access public
     * @param string $message message string
     * @param bool $error true if this is an error rather than informational
     * @param int $statusCode HTTP status code to return
     * @param int $code exception code
     * @param Exception $previous previous exception
     * @return Exception message exception object
     */
    public function __construct($message, $error = false, $statusCode = 200, $code = 0)
    {
        $this->Error = $error;
        $this->StatusCode = $statusCode;
        parent::__construct($message, $code);
    }
}
