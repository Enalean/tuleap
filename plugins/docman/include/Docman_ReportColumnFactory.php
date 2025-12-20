<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

use Tuleap\Docman\Search\FilenameColumnReport;
use Tuleap\Docman\Search\IdColumnReport;

class Docman_ReportColumnFactory //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    public function getColumnFromLabel(string $colLabel): Docman_ReportColumn
    {
        $mdFactory = $this->_getMetadataFactory();
        switch ($colLabel) {
            case 'location':
                $col = new Docman_ReportColumnLocation();
                break;

            case 'id':
                $col = new IdColumnReport();
                break;

            case 'filename':
                $col = new FilenameColumnReport();
                break;

            default:
                $md  = $mdFactory->getFromLabel($colLabel);
                $col = new Docman_ReportColumn($md);
        }
        return $col;
    }

    public function &_getMetadataFactory() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $mdf = new Docman_MetadataFactory($this->groupId);
        return $mdf;
    }
}
