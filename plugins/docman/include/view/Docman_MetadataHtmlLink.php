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
class Docman_MetadataHtmlLink extends \Docman_MetadataHtml
{
    public $link_url;
    public function __construct($link_url)
    {
        $this->link_url = $link_url;
    }
    public function getLabel($show_mandatory_information = \true)
    {
        return \dgettext('tuleap-docman', 'Url:');
    }
    public function getField()
    {
        $hp = \Codendi_HTMLPurifier::instance();
        return '<input type="text" class="docman_text_field" name="item[link_url]" value="' . $hp->purify($this->link_url) . '" />';
    }
    public function &getValidator()
    {
        $msg = \dgettext('tuleap-docman', 'The URL is required.');
        $validator = new \Docman_ValidateValueNotEmpty($this->link_url, $msg);
        return $validator;
    }
}
