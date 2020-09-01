<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * HTML rendering for 'Date' metadata
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlDate extends \Docman_MetadataHtml
{
    public function _getField()
    {
        $field = '';
        $selected = $this->md->getValue();
        if ($selected === \null) {
            $selected = $this->md->getDefaultValue();
        }
        if ($selected != '' && $selected != 0) {
            $selected = \date("Y-n-j", $selected);
        } else {
            $selected = '';
        }
        $name = $this->_getFieldName();
        $field .= \html_field_date($name, $selected, \false, '10', '10', $this->formParams['form_name'], \false);
        return $field;
    }
    public function getValue()
    {
        $v = $this->md->getValue();
        if ($v != \null && $v != '' && $v != 0) {
            $html_purifier = \Codendi_HTMLPurifier::instance();
            return $html_purifier->purify(\format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $v));
        }
        return '';
    }
}
