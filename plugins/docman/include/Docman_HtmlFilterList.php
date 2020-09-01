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
class Docman_HtmlFilterList extends \Docman_HtmlFilter
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    public function buildSelectBox($vals, $txts)
    {
        // Purifying is disabled as $txts already contains purified strings
        $html = \html_build_select_box_from_arrays($vals, $txts, $this->filter->md->getLabel(), $this->filter->getValue(), \false, '', \true, $GLOBALS['Language']->getText('global', 'any'), \false, '', \CODENDI_PURIFIER_DISABLED);
        return $html;
    }
    public function _valueSelectorHtml($formName = 0)
    {
        $vIter = $this->filter->md->getListOfValueIterator();
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();
            if ($e->getStatus() == 'A' || $e->getStatus() == 'P') {
                $vals[] = $e->getId();
                $txts[] = \Docman_MetadataHtmlList::_getElementName($e);
            }
            $vIter->next();
        }
        $html = $this->buildSelectBox($vals, $txts);
        return $html;
    }
}
