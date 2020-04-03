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

class Docman_HtmlFilterFactory
{

    public function __construct()
    {
    }

    public function getFromFilter($filter)
    {
        $f = null;
        if (is_a($filter, 'Docman_FilterDateAdvanced')) {
            $f = new Docman_HtmlFilterDateAdvanced($filter);
        } elseif (is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_HtmlFilterDate($filter);
        } elseif (is_a($filter, 'Docman_FilterListAdvanced')) {
            $f = new Docman_HtmlFilterListAdvanced($filter);
        } elseif (is_a($filter, 'Docman_FilterList')) {
            $f = new Docman_HtmlFilterList($filter);
        } elseif (is_a($filter, 'Docman_FilterText')) {
            $f = new Docman_HtmlFilterText($filter);
        } elseif (is_a($filter, 'Docman_FilterOwner')) {
            $f = new Docman_HtmlFilterText($filter);
        } else {
            $f = new Docman_HtmlFilter($filter);
        }
        return $f;
    }
}

class Docman_HtmlFilter
{
    public $filter;
    public $hp;

    public function __construct($filter)
    {
        $this->filter = $filter;
        $this->hp = Codendi_HTMLPurifier::instance();
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
        if ($value !== null) {
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
            $trashWarn = $this->hp->purify(dgettext('tuleap-docman', 'Are you sure you want to remove this filter from the list?'));
            $trashAlt  = $this->hp->purify(dgettext('tuleap-docman', 'Remove the filter'));
            $trashLink = html_trash_link($trashLink, $trashWarn, $trashAlt);
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

class Docman_HtmlFilterDate extends Docman_HtmlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _valueSelectorHtml($formName)
    {
        $html = '';
        $html .= html_select_operator($this->filter->getFieldOperatorName(), $this->filter->getOperator());
        $html .= html_field_date(
            $this->filter->getFieldValueName(),
            $this->filter->getValue(),
            false,
            '10',
            '10',
            $formName,
            false
        );
        return $html;
    }
}

class Docman_HtmlFilterDateAdvanced extends Docman_HtmlFilterDate
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _valueSelectorHtml($formName)
    {
        $html = '';

        $html .= dgettext('tuleap-docman', 'Start:');
        $html .= '&nbsp;';
        $html .= html_field_date(
            $this->filter->getFieldStartValueName(),
            $this->filter->getValueStart(),
            false,
            '10',
            '10',
            $formName,
            false
        );
        $html .= '&nbsp;';
        $html .= dgettext('tuleap-docman', 'End:');
        $html .= '&nbsp;';
        $html .= html_field_date(
            $this->filter->getFieldEndValueName(),
            $this->filter->getValueEnd(),
            false,
            '10',
            '10',
            $formName,
            false
        );
        return $html;
    }
}


class Docman_HtmlFilterList extends Docman_HtmlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function buildSelectBox($vals, $txts)
    {
        // Purifying is disabled as $txts already contains purified strings
        $html = html_build_select_box_from_arrays($vals, $txts, $this->filter->md->getLabel(), $this->filter->getValue(), false, '', true, $GLOBALS['Language']->getText('global', 'any'), false, '', CODENDI_PURIFIER_DISABLED);
        return $html;
    }

    public function _valueSelectorHtml($formName = 0)
    {
        $vIter = $this->filter->md->getListOfValueIterator();
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();

            if (
                $e->getStatus() == 'A'
                || $e->getStatus() == 'P'
            ) {
                $vals[]  = $e->getId();
                $txts[] = Docman_MetadataHtmlList::_getElementName($e);
            }

            $vIter->next();
        }

        $html = $this->buildSelectBox($vals, $txts);
        return $html;
    }
}

class Docman_HtmlFilterListAdvanced extends Docman_HtmlFilterList
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function buildSelectBox($vals, $txts)
    {
        // Purifying is disabled as $txts already contains purified strings
        $html = html_build_select_box_from_arrays($vals, $txts, $this->filter->md->getLabel(), $this->filter->getValue(), false, '', true, $GLOBALS['Language']->getText('global', 'any'), false, '', CODENDI_PURIFIER_DISABLED);
        return $html;
    }
}

class Docman_HtmlFilterText extends Docman_HtmlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _valueSelectorHtml($formName = 0)
    {
        $html = '';
        $html .= '<input type="text" name="' . $this->filter->md->getLabel() . '" value="' . $this->hp->purify($this->filter->getValue()) . '" class="text_field"/>';
        return $html;
    }
}
