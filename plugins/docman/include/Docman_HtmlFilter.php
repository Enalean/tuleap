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
class Docman_HtmlFilter
{
    public $filter;
    public $hp;
    public function __construct($filter)
    {
        $this->filter = $filter;
        $this->hp = \Codendi_HTMLPurifier::instance();
    }
    public function _fieldName()
    {
        $html = $this->hp->purify($this->filter->md->getName());
        return $html;
    }
    public function _valueSelectorHtml($formName)
    {
        $html = '';
        $value = $this->filter->getValue();
        if ($value !== \null) {
            $html .= '<input type="hidden" name="' . $this->filter->md->getLabel() . '" value="' . $this->hp->purify($value) . '" />';
            $html .= "\n";
        }
        return $html;
    }
    public function toHtml($formName, $trashLinkBase)
    {
        $trashLink = '';
        if ($trashLinkBase) {
            $trashLink = $trashLinkBase . $this->filter->md->getLabel();
            $trashWarn = $this->hp->purify(\dgettext('tuleap-docman', 'Are you sure you want to remove this filter from the list?'));
            $trashAlt = $this->hp->purify(\dgettext('tuleap-docman', 'Remove the filter'));
            $trashLink = \html_trash_link($trashLink, $trashWarn, $trashAlt);
        }
        $html = '<tr>';
        $html .= '<td>';
        $html .= $trashLink;
        $html .= '&nbsp;';
        $html .= $this->_fieldName();
        $html .= ': ';
        $html .= '</td>';
        $html .= '<td>';
        $html .= $this->_valueSelectorHtml($formName);
        $html .= '</td>';
        $html .= '</tr>';
        $html .= "\n";
        return $html;
    }
}
