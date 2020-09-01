<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
class Docman_HtmlFilterDateAdvanced extends \Docman_HtmlFilterDate
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    public function _valueSelectorHtml($formName)
    {
        $html = '';
        $html .= \dgettext('tuleap-docman', 'Start:');
        $html .= '&nbsp;';
        $html .= \html_field_date($this->filter->getFieldStartValueName(), $this->filter->getValueStart(), \false, '10', '10', $formName, \false);
        $html .= '&nbsp;';
        $html .= \dgettext('tuleap-docman', 'End:');
        $html .= '&nbsp;';
        $html .= \html_field_date($this->filter->getFieldEndValueName(), $this->filter->getValueEnd(), \false, '10', '10', $formName, \false);
        return $html;
    }
}
