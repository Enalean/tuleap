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
 * HTML rendering for 'List' metadata
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlList extends \Docman_MetadataHtml
{
    /**
     * static
     */
    public function _getElementName($e, $hideNone = \false)
    {
        $hp = \Codendi_HTMLPurifier::instance();
        $name = '';
        switch ($e->getId()) {
            case 100:
                if (! $hideNone) {
                    $name = \dgettext('tuleap-docman', 'None');
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
                $name = \dgettext('tuleap-docman', 'None');
                break;
            default:
                $hp = \Codendi_HTMLPurifier::instance();
                $name = $hp->purify($e->getDescription());
        }
        return $name;
    }
    public function getValue($hideNone = \false)
    {
        $vIter = $this->md->getValue();
        $html = '';
        $first = \true;
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();
            if (! $first) {
                $html .= '<br>';
            }
            $html .= $this->_getElementName($e, $hideNone);
            $first = \false;
            $vIter->next();
        }
        return $html;
    }
    public function _getField()
    {
        $html = '';
        // First is their any value already selected
        $selectedElements = [];
        $eIter = $this->md->getValue();
        if ($eIter != \null) {
            //@todo: a toArray() method in ArrayIterator maybe useful here.
            $eIter->rewind();
            while ($eIter->valid()) {
                $e = $eIter->current();
                $selectedElements[] = $e->getId();
                $eIter->next();
            }
        }
        // If no values selected, select the default value
        if (\count($selectedElements) == 0) {
            $dfltValue = $this->md->getDefaultValue();
            if (\is_array($dfltValue)) {
                $selectedElements = $dfltValue;
            } else {
                $selectedElements[] = $dfltValue;
            }
        }
        $name = $this->_getFieldName();
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
            if (\in_array($e->getId(), $selectedElements)) {
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
        $validator = \null;
        if ($this->md->canChangeValue() && ! $this->md->isEmptyAllowed()) {
            $validator = new \Docman_ValidateMetadataListIsNotEmpty($this->md);
        }
        return $validator;
    }
}
