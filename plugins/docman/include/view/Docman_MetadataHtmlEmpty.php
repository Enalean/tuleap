<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlEmpty extends \Docman_MetadataHtml
{
    public function __construct()
    {
    }
    public function getLabel($show_mandatory_information = \true)
    {
        return \dgettext('tuleap-docman', 'An empty document has no type. You will be able to choose document\'s type later. It\'s useful to create an entry in a hierarchy without the actual document.');
    }
    public function getField()
    {
        return '';
    }
    public function &getValidator()
    {
        $validator = \null;
        return $validator;
    }
}
