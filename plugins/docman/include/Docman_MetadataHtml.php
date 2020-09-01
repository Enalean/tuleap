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
 * Rendering of metadata using HTML language.
 *
 * This class aims to provide the elements to render metadata to final user in
 * both read and write mode. This class is abstract and there is at least a
 * subclass per metadata type. This class can also be overloaded for specific
 * rendering of some fields (eg. obsolescence_date is stored as a date but it's
 * more convenient for final user to display it as a select box with duration).
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtml
{
    public $md;
    public $formParams;
    public $hp;
    public function __construct(&$md, $formParams)
    {
        $this->md = $md;
        $this->hp = \Codendi_HTMLPurifier::instance();
        $this->formParams = $formParams;
    }
    /**
     * Return end user field title.
     *
     * @return string
     */
    public function getLabel($show_mandatory_information = \true)
    {
        $desc = $this->md->getDescription();
        $html = '';
        $html .= '<span title="' . $this->hp->purify($desc) . '">';
        if ($this->md->isSpecial()) {
            switch ($this->md->getLabel()) {
                case 'description':
                    $html .= \dgettext('tuleap-docman', 'Description:');
                    break;
                case 'rank':
                    $html .= \dgettext('tuleap-docman', 'Rank:');
                    break;
                case 'title':
                    $html .= \dgettext('tuleap-docman', 'Title:');
                    break;
                case 'owner':
                    $html .= \dgettext('tuleap-docman', 'Owner:');
                    break;
                case 'status':
                    $html .= \dgettext('tuleap-docman', 'Status:');
                    break;
                case 'obsolescence_date':
                    $html .= \dgettext('tuleap-docman', 'Validity:');
                    break;
                case 'create_date':
                    $html .= \dgettext('tuleap-docman', 'Creation date:');
                    break;
                case 'update_date':
                    $html .= \dgettext('tuleap-docman', 'Last update date:');
                    break;
            }
        } else {
            $html .= $this->hp->purify($this->md->getName()) . ":";
        }
        if ($show_mandatory_information && $this->md->canChangeValue() && ! $this->md->isEmptyAllowed()) {
            $html .= '&nbsp;';
            $html .= '<span class="highlight">*</span>';
        }
        $html .= '</span>';
        return $html;
    }
    /**
     * Return HTML field name.
     *
     * @return string
     */
    public function _getFieldName()
    {
        $lbl = $this->md->getLabel();
        if ($this->md->isSpecial()) {
            $name = 'item[' . $lbl . ']';
        } else {
            $name = 'metadata[' . $lbl . ']';
        }
        return $name;
    }
    /**
     * Return HTML form element corresponding to the metadata.
     *
     * @return string
     */
    public function getField()
    {
        if ($this->md->canChangeValue()) {
            $html = $this->_getField();
        } else {
            $html = $this->getValue();
        }
        return $html;
    }
    /**
     * Return metadata value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->md->getValue();
    }
    /**
     * Return field input validator.
     *
     * @return Docman_Validator|null
     */
    public function &getValidator()
    {
        $validator = \null;
        if ($this->md->canChangeValue() && ! $this->md->isEmptyAllowed()) {
            $validator = new \Docman_ValidateMetadataIsNotEmpty($this->md);
        }
        return $validator;
    }
}
