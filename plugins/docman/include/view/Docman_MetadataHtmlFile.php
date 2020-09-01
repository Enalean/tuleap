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
class Docman_MetadataHtmlFile extends \Docman_MetadataHtml
{
    public function __construct()
    {
    }
    public function getLabel($show_mandatory_information = \true)
    {
        return \dgettext('tuleap-docman', 'Content:');
    }
    public function getField()
    {
        $html = '<input type="file" name="file" />';
        $html .= '<br /><em>' . \sprintf(\dgettext('tuleap-docman', '(The maximum upload file size is %1$s MByte)'), \formatByteToMb((int) \ForgeConfig::get(\DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING))) . '</em>';
        return $html;
    }
    public function &getValidator($request = \null)
    {
        if ($request === \null) {
            $request = \HTTPRequest::instance();
        }
        $validator = new \Docman_ValidateUpload($request);
        return $validator;
    }
}
