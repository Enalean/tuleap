<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Docman_ValidateFilterDate extends \Docman_ValidateFilter
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    #[\Override]
    public function validate()
    {
        if ($this->isValid === \null) {
            $this->isValid = \false;
            if ($this->filter->getValue() == '') {
                $this->isValid = \true;
            } elseif (\preg_match('/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/', $this->filter->getValue())) {
                $this->isValid = \true;
            } else {
                $today         = \date('Y-n-j');
                $this->message = \sprintf(\dgettext('tuleap-docman', 'The date entered in field "%1$s" is not valid. Valid date format is YYYY-M-D (e.g. \'%2$s\' for today) or let the field blank.'), $this->filter->md->getName(), $today);
            }
        }
        return $this->isValid;
    }
}
