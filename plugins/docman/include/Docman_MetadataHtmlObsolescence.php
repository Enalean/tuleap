<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Date\DateHelper;

/**
 * HTML rendering for special 'obsolescence_date' metadata
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_MetadataHtmlObsolescence extends \Docman_MetadataHtml
{
    public function getValue()
    {
        $v = $this->md->getValue();
        switch ($v) {
            case \PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT:
                return \dgettext('tuleap-docman', 'Permanent');
            default:
                return DateHelper::formatForLanguage($GLOBALS['Language'], $v, \true);
        }
    }

    public function _getField() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $labels        = [\PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT => \dgettext('tuleap-docman', 'Permanent'), 3 => \dgettext('tuleap-docman', '3 Months from today'), 6 => \dgettext('tuleap-docman', '6 Months from today'), 12 => \dgettext('tuleap-docman', '12 Months from today'), 100 => \dgettext('tuleap-docman', 'Fixed date'), 200 => \dgettext('tuleap-docman', 'Obsolete today')];
        $selected      = $this->md->getValue();
        $selectedInput = '';
        if ($selected === \null) {
            $selected = $this->md->getDefaultValue();
        } else {
            if ($selected != 0) {
                $selectedInput = \date("Y-n-j", $selected);
                $selected      = 100;
            }
        }
        $name      = 'validity';
        $inputname = $this->_getFieldName();
        $field     = '';
        $field    .= '<select name="' . $name . '" onchange="javascript:change_obsolescence_date(document.forms.' . $this->formParams['form_name'] . ')" id="' . $this->md->getLabel() . '">' . "\n";
        foreach ($labels as $value => $label) {
            $select = '';
            if ($value == $selected) {
                $select = ' selected="selected"';
            }
            $field .= '<option value="' . $value . '"' . $select . '>' . $label . '</option>' . "\n";
        }
        $field .= '</select>' . "\n";
        $field .= '&nbsp;<em>' . \dgettext('tuleap-docman', 'Corresponding date:') . '</em>';
        $field .= \html_field_date($inputname, $selectedInput, \false, '10', '10', $this->formParams['form_name'], \false);
        return $field;
    }
}
