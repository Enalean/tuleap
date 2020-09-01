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
 * HTML rendering for 'String' metadata
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlString extends \Docman_MetadataHtml
{
    public function getValue()
    {
        $value = $this->hp->purify($this->md->getValue(), \CODENDI_PURIFIER_BASIC, $this->md->getGroupId());
        return $value;
    }
    public function _getField()
    {
        $value = $this->md->getValue();
        if ($value === \null) {
            $value = $this->md->getDefaultValue();
        }
        $value = $this->hp->purify($value);
        $field = '<input type="text" class="text_field" name="' . $this->_getFieldName() . '" value="' . $value . '" id="' . $this->md->getLabel() . '" data-test="' . $this->md->getLabel() . '"  />';
        return $field;
    }
}
