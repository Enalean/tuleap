<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

require_once('Docman_Metadata.class.php');

class Docman_MetadataHtmlFactory {
    
    function &getFromMetadata(&$md, $formParams) {
        $mdh = null;

        switch($md->getLabel()) {
        case 'owner':
            $mdh = new Docman_MetadataHtmlOwner($md, $formParams);;
            break;

        case 'obsolescence_date':
            $mdh = new Docman_MetadataHtmlObsolescence($md, $formParams);;
            break;
        }

        if($mdh === null) {
            switch($md->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                $mdh = new Docman_MetadataHtmlText($md, $formParams);;
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                $mdh = new Docman_MetadataHtmlString($md, $formParams);;
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $mdh = new Docman_MetadataHtmlDate($md, $formParams);;
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $mdh = new Docman_MetadataHtmlList($md, $formParams);;
                break;
            default:
            }    
        }      

        return $mdh;
    }

    function buildFieldArray($mdIter, $mdla, $whitelist, $formName, $themePath) {
        $fields = array();
        $formParams = array('form_name' => $formName,
                            'theme_path' => $themePath);

        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            if(($whitelist && isset($mdla[$md->getLabel()]))
               || (!$whitelist && !isset($mdla[$md->getLabel()]))) {
                $fields[$md->getLabel()] = $this->getFromMetadata($md, $formParams);
            }
            $mdIter->next();
        }
        return $fields;
    }

}

class Docman_ValidateMetadataIsNotEmpty extends Docman_Validator {
    function Docman_ValidateMetadataIsNotEmpty(&$md) {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'md_error_empty_gen', array($md->getName()));
        if($md !== null) { 
            $val = $md->getValue();
            if($val === null || $val == '') {
                $this->addError($msg);
            }
        }
        else {
            $this->addError($msg);
        }
    }
}

class Docman_ValidateMetadataListIsNotEmpty extends Docman_Validator {
    function Docman_ValidateMetadataListIsNotEmpty(&$md) {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'md_error_empty_gen', array($md->getName()));
        if($md !== null) {
            $selectedElements = array();
            $vIter = $md->getValue();

            $vIter->rewind();
            while($vIter->valid()) {
                $e = $vIter->current();

                $selectedElements[] = $e->getId();

                $vIter->next();
            }

            if(count($selectedElements) <= 1 && isset($selectedElements[0]) && $selectedElements[0] == 100) {
                $this->addError($msg);
            }
        }
        else {
            $this->addError($msg);
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
class Docman_MetadataHtml {
    var $md;
    var $formParams;
    var $hp;

    function Docman_MetadataHtml(&$md, $formParams) {
        $this->md =& $md;
        $this->hp =& CodeX_HTMLPurifier::instance();
        $this->formParams = $formParams; 
    }

    /**
     * Return end user field title.
     *
     * @return string
     */
    function getLabel($show_mandatory_information = true) {
        $desc = $this->md->getDescription();
        $html = '';
        $html .= '<span title="'. $this->hp->purify($desc) .'">';
        if($this->md->isSpecial()) {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'field_'.$this->md->getLabel());
        }
        else {
            $html .= $this->hp->purify($this->md->getName()) .":";
        }
        if($show_mandatory_information && $this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
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
    function _getFieldName() {
        $lbl = $this->md->getLabel();
        if($this->md->isSpecial()) {
            $name  = 'item['.$lbl.']';
        }
        else {
            $name  = 'metadata['.$lbl.']';
        }
        return $name;
    }

    /**
     * Return HTML form element corresponding to the metadata.
     *
     * @return string
     */
    function getField() {
        if($this->md->canChangeValue()) {
            $html = $this->_getField();
        }
        else {
            $html = $this->getValue();
        }
        return $html;
    }
    
    /**
     * Return metadata value.
     *
     * @return string
     */
    function getValue() {
        return $this->md->getValue();
    }

    /**
     * Return field input validator.
     *
     * @return string
     */
    function &getValidator() {
        $validator = null;
        if(/*$show_mandatory_information && */$this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
            $validator = new Docman_ValidateMetadataIsNotEmpty($this->md);
        }
        return $validator;
    }
}

/**
 * HTML rendering for 'Text' metadata
 */
class Docman_MetadataHtmlText extends Docman_MetadataHtml {

    function getValue() {
        $value = $this->hp->purify($this->md->getValue(), CODEX_PURIFIER_BASIC, $this->md->getGroupId());
        return $value;
    }

    function _getField() {
        $name  = $this->_getFieldName();
        $value = $this->md->getValue();
        if($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $value = $this->hp->purify($value);
        $field = '<textarea name="'.$name.'" id="'.$this->md->getLabel().'">'.$value.'</textarea>';
        return $field;
    }
}

/**
 * HTML rendering for 'String' metadata
 */
class Docman_MetadataHtmlString extends Docman_MetadataHtml {

    function getValue() {
        $value = $this->hp->purify($this->md->getValue(), CODEX_PURIFIER_BASIC, $this->md->getGroupId());
        return $value;
    }

    function _getField() {
        $value = $this->md->getValue();
        if($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $value = $this->hp->purify($value);
        $field = '<input type="text" class="text_field" name="'.$this->_getFieldName().'" value="'.$value.'" id="'.$this->md->getLabel().'" />';
        return $field;
    }
}

/**
 * HTML rendering for 'Date' metadata
 */
class Docman_MetadataHtmlDate extends Docman_MetadataHtml {

    function _getField() {
        $field = '';

        $selected = $this->md->getValue();
        if($selected === null) {
            $selected = $this->md->getDefaultValue();
        }
        if($selected != '' && $selected != 0) {
            $selected = date("Y-n-j", $selected);
        }
        else {
            $selected = '';
        }

        $name  = $this->_getFieldName();

        $field .= html_field_date($name,
                                  $selected,
                                  false,
                                  '10',
                                  '10',
                                  $this->formParams['form_name'],
                                  false);        

        return $field;
    }

    function getValue() {
        $v = $this->md->getValue();
        if($v != null && $v != '' && $v != 0) {
            return strftime("%e %b %Y", $v);
        }
    }
}

/**
 * HTML rendering for 'List' metadata
 */
class Docman_MetadataHtmlList extends Docman_MetadataHtml {

    /**
     * static
     */
    function _getElementName(&$e, $hideNone=false) {
        $hp =& CodeX_HTMLPurifier::instance();
        $name = '';
        switch($e->getId()) {
        case 100:
            if(!$hideNone) {
                $name = $GLOBALS['Language']->getText('plugin_docman', 'love_special_none_name_key');
            }
            break;
        default:
            $name = $hp->purify($e->getName());
        }
        return $name;
    }

    function _getElementDescription(&$e) {
        $name = '';
        switch($e->getId()) {
        case 100:
            $name = $GLOBALS['Language']->getText('plugin_docman', 'love_special_none_desc_key');
            break;
        default:
            $name = $this->hp->purify($e->getDescription());
        }
        return $name;
    }
    

    function getValue($hideNone=false) {
        $vIter = $this->md->getValue();

        $html = '';
        $first = true;
        $vIter->rewind();
        while($vIter->valid()) {
            $e = $vIter->current();

            if(!$first) {
                $html .= '<br>';
            }
            $html .= $this->_getElementName($e, $hideNone);

            $first = false;
            $vIter->next();
        }

        return $html;
    }

    function _getField() {
        $html = '';
        // First is their any value already selected
        $selectedElements = array();
        $eIter = $this->md->getValue();
        if($eIter != null) {
            //@todo: a toArray() method in ArrayIterator maybe useful here.
            $eIter->rewind();
            while($eIter->valid()) {
                $e = $eIter->current();
                $selectedElements[] = $e->getId();
                $eIter->next();
            }
        }

        // If no values selected, select the default value
        if(count($selectedElements) == 0) {
            $dfltValue = $this->md->getDefaultValue();
            if(is_array($dfltValue)) {
                $selectedElements = $dfltValue;
            } else {
                $selectedElements[] = $dfltValue;
            }
        }

        $name     = $this->_getFieldName();
        $multiple = '';
        if($this->md->isMultipleValuesAllowed()) {
            $name = $name.'[]';
            $multiple = ' multiple = "multiple" size = "6"';
        }
        
        $html .= '<select name="'.$name.'"'.$multiple.' id="'.$this->md->getLabel().'">'."\n";

        $vIter = $this->md->getListOfValueIterator();
        $vIter->rewind();
        while($vIter->valid()) {
            $e = $vIter->current();
            
            $selected = '';
            if(in_array($e->getId(), $selectedElements)) {
                $selected = ' selected="selected"';
            }

            $html .= '<option value="'.$e->getId().'"'.$selected.'>'.$this->_getElementName($e).'</option>'."\n";
            
            $vIter->next();
        }        
        $html .= '</select>'."\n";
        return $html;
    }

    function &getValidator() {
        $validator = null;
        if(/*$show_mandatory_information && */$this->md->canChangeValue() && !$this->md->isEmptyAllowed()) {
            $validator = new Docman_ValidateMetadataListIsNotEmpty($this->md);
        }
        return $validator;
    }


}

/**
 * HTML rendering for special 'obsolescence_date' metadata
 */
class Docman_MetadataHtmlObsolescence extends Docman_MetadataHtml {

    function getValue() {
        $v = $this->md->getValue();
        switch($v) {
        case PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT:
            return $GLOBALS['Language']->getText('plugin_docman','md_html_validity_permanent');
            break;
        default:
            return util_timestamp_to_userdateformat($v, true);
        }
    }

    function _getField() {
        $labels = array(PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_permanent'),
                        3 => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_3_months'), 
                        6 => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_6_months'), 
                        12 => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_12_months'),
                        100 => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_fixed_date'),
                        200 => $GLOBALS['Language']->getText('plugin_docman','md_html_validity_today'));
        
        $selected = $this->md->getValue();
        $selectedInput = '';
        if($selected === null) {
            $selected = $this->md->getDefaultValue();
        }
        else {
            if($selected != 0) {
                $selectedInput = date("Y-n-j", $selected);
                $selected = 100;
            }            
        }

        $name = 'validity';
        $inputname = $this->_getFieldName();
     
        $field = '';
        $field .= '<select name="'.$name.'" onchange="javascript:change_obsolescence_date(document.forms.'.$this->formParams['form_name'].')" id="'.$this->md->getLabel().'">'."\n";
        foreach($labels as $value => $label) {
            $select = '';
            if($value == $selected) {
                $select = ' selected="selected"';
            }
            $field .= '<option value="'.$value.'"'.$select.'>'.$label.'</option>'."\n";
        }
        $field .= '</select>'."\n";

        $field .= '&nbsp;<em>'.$GLOBALS['Language']->getText('plugin_docman','md_html_validity_corresp_date').'</em>';
        
        $field .= html_field_date($inputname,
                                  $selectedInput,
                                  false,
                                  '10',
                                  '10',
                                  $this->formParams['form_name'],
                                  false);        

        return $field;
    }
}

/**
 * HTML rendering for special 'owner' metadata
 */
class Docman_MetadataHtmlOwner extends Docman_MetadataHtmlString {

    function getValue() {
        $v = $this->md->getValue();
        if($v != null && $v != '') {
            return user_get_name_display_from_id($v);
        }
        else {
            return '';
        }
    }

    function _getHtmlValue() {
        return $this->getValue();
    }

    function _getField() {
        $name  = $this->_getFieldName();
        $value = $this->md->getValue();
        if($value === null) {
            $value = $this->md->getDefaultValue();
        }
        $v = '';
        if($value != null && $value != '' && $value > 0) {
            $v = user_getname($value);
        }
        $field = '<input type="text" class="text_field" name="'.$this->_getFieldName().'" value="'.$v.'" />';
        return $field;
    }

}

?>
