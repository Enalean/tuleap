<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * Check that submitted value is a simple string and a valid email.
 */
class Valid_Email extends \Valid_String // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $separator;
    public function __construct($key = \null, $separator = \null)
    {
        if (\is_string($separator)) {
            $this->separator = $separator;
        } else {
            $this->separator = \null;
        }
        parent::__construct($key);
    }
    public function validate($value)
    {
        $this->addRule(new \Rule_Email($this->separator));
        return parent::validate($value);
    }
}
