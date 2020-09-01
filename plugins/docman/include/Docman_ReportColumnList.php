<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ReportColumnList extends \Docman_ReportColumn
{
    public function __construct($md)
    {
        parent::__construct($md);
    }
    public function getTableBox($item, $view, $params)
    {
        $mdHtml = $this->_getMdHtml($item);
        if ($mdHtml !== \null) {
            return $mdHtml->getValue(\true);
        }
        return '';
    }
}
