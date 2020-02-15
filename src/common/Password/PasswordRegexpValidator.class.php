<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * PasswordRegexpValidator
 *
 * Validate a password with a regexp
 *
 */
/* abstract */ class PasswordRegexpValidator extends PasswordValidator
{

    public $regexp;

    /**
    * Constructor
    */
    public function __construct($regexp, $description)
    {
        parent::__construct($description);
        $this->regexp = $regexp;
    }

    public function validate($password)
    {
        return preg_match($this->regexp, $password);
    }
}
