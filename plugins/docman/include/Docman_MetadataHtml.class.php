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

class Docman_MetadataHtmlFactory
{

    public function getFromMetadata($md, $formParams)
    {
        $mdh = null;

        switch ($md->getLabel()) {
            case 'owner':
                $mdh = new Docman_MetadataHtmlOwner($md, $formParams);
                break;

            case 'obsolescence_date':
                $mdh = new Docman_MetadataHtmlObsolescence($md, $formParams);
                break;
        }

        if ($mdh === null) {
            switch ($md->getType()) {
                case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                    $mdh = new Docman_MetadataHtmlText($md, $formParams);
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $mdh = new Docman_MetadataHtmlString($md, $formParams);
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    $mdh = new Docman_MetadataHtmlDate($md, $formParams);
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    $mdh = new Docman_MetadataHtmlList($md, $formParams);
                    break;
                default:
            }
        }

        return $mdh;
    }

    public function buildFieldArray($mdIter, $mdla, $whitelist, $formName, $themePath)
    {
        $fields = array();
        $formParams = array('form_name' => $formName,
                            'theme_path' => $themePath);

        foreach ($mdIter as $md) {
            if (
                ($whitelist && isset($mdla[$md->getLabel()]))
                || (!$whitelist && !isset($mdla[$md->getLabel()]))
            ) {
                $fields[$md->getLabel()] = $this->getFromMetadata($md, $formParams);
            }
        }
        return $fields;
    }
}

class Docman_ValidateMetadataIsNotEmpty extends Docman_Validator
{
    public function __construct(&$md)
    {
        $msg = sprintf(dgettext('tuleap-docman', '"%1$s" is required, please fill the field.'), $md->getName());
        if ($md !== null) {
            $val = $md->getValue();
            if ($val === null || $val == '') {
                $this->addError($msg);
            }
        } else {
            $this->addError($msg);
        }
    }
}

class Docman_ValidateMetadataListIsNotEmpty extends Docman_Validator
{
    public function __construct(&$metadata)
    {
        $msg = sprintf(dgettext('tuleap-docman', '"%1$s" is required, please fill the field.'), $metadata->getName());

        if ($metadata !== null) {
            $selected_elements = array();

            $vIter = $metadata->getValue();
            $vIter->rewind();

            while ($vIter->valid()) {
                $current_value       = $vIter->current();
                $selected_elements[] = $current_value->getId();

                $vIter->next();
            }

            if (! $this->metadataIsRequieredAndAtLeastOneValueIsSelected($metadata, $selected_elements)) {
                $this->addError($msg);
            }
        } else {
            $this->addError($msg);
        }
    }

    private function metadataIsRequieredAndAtLeastOneValueIsSelected(Docman_ListMetadata $metadata, array $selectedElements)
    {
        if ($metadata->isEmptyAllowed()) {
            return true;
        } elseif (count($selectedElements) > 1) {
            return true;
        } elseif (count($selectedElements) === 1 && isset($selectedElements[0]) && $selectedElements[0] != 100) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Rendering of metadata using HTML language.
 *
 * This class aims to provide the elements to render metadata to final user in
 * both read and write mode. This class is abstract and there is at least a
 * subclass per metadata type. This class can also be overloaded for specific
 * rendering of some fields (eg. obsolescence_date is stored as a date but it's
 * more convenient for final user to display it as a select box with duration).
 */
class Docman_MetadataHtml
{
    public $md;
    public $formParams;
    public $hp;

    public function __construct(&$md, $formParams)
    {
        $this->md = $md;
        $this->hp = Codendi_HTMLPurifier::instance();
        $this->formParams = $formParams;
    }

    /**
     * Return end user field title.
     *
     * @return string
     */
    public function getLabel($show_mandatory_information = true)
    {
        $desc = $this->md->getDescription();
        $html = '';
        $html .= '<span title="' . $this->hp->purify($desc) . '">';
        if ($this->md->isSpecial()) {
            switch ($this->md->getLabel()) {
                case 'description':
                    $html .= dgettext('tuleap-docman', 'Description:');
                    break;
                case 'rank':
                    $html .= dgettext('tuleap-docman', 'Rank:');
                    break;
                case 'title':
                    $html .= dgettext('tuleap-docman', 'Title:');
                    break;
                case 'owner':
                    $html .= dgettext('tuleap-docman', 'Owner:');
                    break;
                case 'status':
                    $html .= dgettext('tuleap-docman', 'Status:');
                    break;
                case 'obsolescence_date':
                    $html .= dgettext('tuleap-docman', 'Validity:');
                    break;
                case 'create_date':
                    $html .= dgettext('tuleap-docman', 'Creation date:');
                    break;
                case 'update_date':
                    $html .= dgettext('tuleap-docman', 'Last update date:');
                    break;
            }
        } else {
            $html .= $this->hp->purify($this->md->getName()) . ":";
        }
        if ($show_mandatory_information && $this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
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
            $name  = 'item[' . $lbl . ']';
        } else {
            $name  = 'metadata[' . $lbl . ']';
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
        $validator = null;
        if (/*$show_mandatory_information && */$this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
            $validator = new Docman_ValidateMetadataIsNotEmpty($this->md);
        }
        return $validator;
    }
}

/**
 * HTML rendering for 'Text' metadata
 */
class Docman_MetadataHtmlText extends Docman_MetadataHtml
{

    public function getValue()
    {
        $value = $this->hp->purify($this->md->getValue(), CODENDI_PURIFIER_BASIC, $this->md->getGroupId());
        return $value;
    }

    public function _getField()
    {
        $name  = $this->_getFieldName();
        $value = $this->md->getValue();
        if ($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $value = $this->hp->purify($value);
        $field = '<textarea name="' . $name . '" id="' . $this->md->getLabel() . '">' . $value . '</textarea>';
        return $field;
    }
}

/**
 * HTML rendering for 'String' metadata
 */
class Docman_MetadataHtmlString extends Docman_MetadataHtml
{

    public function getValue()
    {
        $value = $this->hp->purify($this->md->getValue(), CODENDI_PURIFIER_BASIC, $this->md->getGroupId());
        return $value;
    }

    public function _getField()
    {
        $value = $this->md->getValue();
        if ($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $value = $this->hp->purify($value);
        $field = '<input type="text" class="text_field" name="' . $this->_getFieldName() . '" value="' . $value . '" id="' . $this->md->getLabel() . '" />';
        return $field;
    }
}

/**
 * HTML rendering for 'Date' metadata
 */
class Docman_MetadataHtmlDate extends Docman_MetadataHtml
{

    public function _getField()
    {
        $field = '';

        $selected = $this->md->getValue();
        if ($selected === null) {
            $selected = $this->md->getDefaultValue();
        }
        if ($selected != '' && $selected != 0) {
            $selected = date("Y-n-j", $selected);
        } else {
            $selected = '';
        }

        $name  = $this->_getFieldName();

        $field .= html_field_date(
            $name,
            $selected,
            false,
            '10',
            '10',
            $this->formParams['form_name'],
            false
        );

        return $field;
    }

    public function getValue()
    {
        $v = $this->md->getValue();
        if ($v != null && $v != '' && $v != 0) {
            $html_purifier = Codendi_HTMLPurifier::instance();
            return $html_purifier->purify(format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $v));
        }
        return '';
    }
}

/**
 * HTML rendering for 'List' metadata
 */
class Docman_MetadataHtmlList extends Docman_MetadataHtml
{

    /**
     * static
     */
    public function _getElementName($e, $hideNone = false)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $name = '';
        switch ($e->getId()) {
            case 100:
                if (!$hideNone) {
                    $name = dgettext('tuleap-docman', 'None');
                }
                break;
            default:
                $name = $hp->purify($e->getName());
        }
        return $name;
    }

    public static function _getElementDescription($e)
    {
        $name = '';
        switch ($e->getId()) {
            case 100:
                $name = dgettext('tuleap-docman', 'None');
                break;
            default:
                $hp = Codendi_HTMLPurifier::instance();
                $name = $hp->purify($e->getDescription());
        }
        return $name;
    }


    public function getValue($hideNone = false)
    {
        $vIter = $this->md->getValue();

        $html = '';
        $first = true;
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();

            if (!$first) {
                $html .= '<br>';
            }
            $html .= $this->_getElementName($e, $hideNone);

            $first = false;
            $vIter->next();
        }

        return $html;
    }

    public function _getField()
    {
        $html = '';
        // First is their any value already selected
        $selectedElements = array();
        $eIter = $this->md->getValue();
        if ($eIter != null) {
            //@todo: a toArray() method in ArrayIterator maybe useful here.
            $eIter->rewind();
            while ($eIter->valid()) {
                $e = $eIter->current();
                $selectedElements[] = $e->getId();
                $eIter->next();
            }
        }

        // If no values selected, select the default value
        if (count($selectedElements) == 0) {
            $dfltValue = $this->md->getDefaultValue();
            if (is_array($dfltValue)) {
                $selectedElements = $dfltValue;
            } else {
                $selectedElements[] = $dfltValue;
            }
        }

        $name     = $this->_getFieldName();
        $multiple = '';
        if ($this->md->isMultipleValuesAllowed()) {
            $name = $name . '[]';
            $multiple = ' multiple = "multiple" size = "6"';
        }

        $html .= '<select name="' . $name . '"' . $multiple . ' id="' . $this->md->getLabel() . '">' . "\n";

        $vIter = $this->md->getListOfValueIterator();
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();

            $selected = '';
            if (in_array($e->getId(), $selectedElements)) {
                $selected = ' selected="selected"';
            }

            $html .= '<option value="' . $e->getId() . '"' . $selected . '>' . $this->_getElementName($e) . '</option>' . "\n";

            $vIter->next();
        }
        $html .= '</select>' . "\n";
        return $html;
    }

    public function &getValidator()
    {
        $validator = null;
        if (/*$show_mandatory_information && */$this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
            $validator = new Docman_ValidateMetadataListIsNotEmpty($this->md);
        }
        return $validator;
    }
}

/**
 * HTML rendering for special 'obsolescence_date' metadata
 */
class Docman_MetadataHtmlObsolescence extends Docman_MetadataHtml
{

    public function getValue()
    {
        $v = $this->md->getValue();
        switch ($v) {
            case PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT:
                return dgettext('tuleap-docman', 'Permanent');
            break;
            default:
                return DateHelper::formatForLanguage($GLOBALS['Language'], $v, true);
        }
    }

    public function _getField()
    {
        $labels = array(PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT => dgettext('tuleap-docman', 'Permanent'),
                        3 => dgettext('tuleap-docman', '3 Months from today'),
                        6 => dgettext('tuleap-docman', '6 Months from today'),
                        12 => dgettext('tuleap-docman', '12 Months from today'),
                        100 => dgettext('tuleap-docman', 'Fixed date'),
                        200 => dgettext('tuleap-docman', 'Obsolete today'));

        $selected = $this->md->getValue();
        $selectedInput = '';
        if ($selected === null) {
            $selected = $this->md->getDefaultValue();
        } else {
            if ($selected != 0) {
                $selectedInput = date("Y-n-j", $selected);
                $selected = 100;
            }
        }

        $name = 'validity';
        $inputname = $this->_getFieldName();

        $field = '';
        $field .= '<select name="' . $name . '" onchange="javascript:change_obsolescence_date(document.forms.' . $this->formParams['form_name'] . ')" id="' . $this->md->getLabel() . '">' . "\n";
        foreach ($labels as $value => $label) {
            $select = '';
            if ($value == $selected) {
                $select = ' selected="selected"';
            }
            $field .= '<option value="' . $value . '"' . $select . '>' . $label . '</option>' . "\n";
        }
        $field .= '</select>' . "\n";

        $field .= '&nbsp;<em>' . dgettext('tuleap-docman', 'Corresponding date:') . '</em>';

        $field .= html_field_date(
            $inputname,
            $selectedInput,
            false,
            '10',
            '10',
            $this->formParams['form_name'],
            false
        );

        return $field;
    }
}

/**
 * HTML rendering for special 'owner' metadata
 */
class Docman_MetadataHtmlOwner extends Docman_MetadataHtmlString
{

    public function getValue()
    {
        $v = $this->md->getValue();
        if ($v != null && $v != '') {
            $hp = Codendi_HTMLPurifier::instance();
            $uh = UserHelper::instance();
            return $hp->purify($uh->getDisplayNameFromUserId($v));
        } else {
            return '';
        }
    }

    public function _getField()
    {
        $name  = $this->_getFieldName();
        $value = $this->md->getValue();
        if ($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $v = '';
        if ($value != null && $value != '' && $value > 0) {
            $v = user_getname($value);
        }
        $field = '<input type="text" class="text_field" name="' . $this->_getFieldName() . '" value="' . $v . '" />';
        return $field;
    }
}
