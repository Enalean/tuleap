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
 * HTML rendering for special 'owner' metadata
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlOwner extends \Docman_MetadataHtmlString
{
    public function getValue()
    {
        $v = $this->md->getValue();
        if ($v != \null && $v != '') {
            $hp = \Codendi_HTMLPurifier::instance();
            $uh = \UserHelper::instance();
            return $hp->purify($uh->getDisplayNameFromUserId($v));
        } else {
            return '';
        }
    }
    public function _getField()
    {
        $name = $this->_getFieldName();
        $value = $this->md->getValue();
        if ($value === \null) {
            $value = $this->md->getDefaultValue();
        }
        $v = '';
        if ($value != \null && $value != '' && $value > 0) {
            $v = \user_getname($value);
        }
        $field = '<input type="text" class="text_field" name="' . $this->_getFieldName() . '" value="' . $v . '" />';
        return $field;
    }
}
