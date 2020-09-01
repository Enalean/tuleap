<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
class Docman_ReportColumn
{
    public $md;
    public $sort;
    public function __construct($md)
    {
        $this->md = $md;
        $this->sort = \null;
    }
    public function setSort($s)
    {
        $this->sort = $s;
    }
    public function getSort()
    {
        return $this->sort;
    }
    public function getSortParameter()
    {
        $sortParam = \null;
        if ($this->md !== \null) {
            $sortParam = 'sort_' . $this->md->getLabel();
        }
        return $sortParam;
    }
    public function getSortSelectorHtml()
    {
        $html = '';
        $sort = $this->getSort();
        if ($sort !== \null) {
            $html .= '<input type="hidden" name="' . $this->getSortParameter() . '" value="' . $sort . '" />';
            $html .= "\n";
        }
        return $html;
    }
    public function getTitle($view, $viewParams)
    {
        $sort = $this->getSort();
        if ($sort == 1) {
            $toggleValue = '0';
            $toogleIcon = '<img src="' . \util_get_image_theme("up_arrow.png") . '" border="0" >';
        } else {
            $toggleValue = '1';
            $toogleIcon = '<img src="' . \util_get_image_theme("dn_arrow.png") . '" border="0" >';
        }
        // URL
        $toggleParam = [];
        $sortParam = $this->getSortParameter();
        if ($sortParam !== \null) {
            $toggleParam[$sortParam] = $toggleValue;
        }
        $url = $view->_buildSearchUrl($viewParams, [$sortParam => $toggleValue]);
        $title = \dgettext('tuleap-docman', 'Click on title to toggle table sort');
        $purifier = \Codendi_HTMLPurifier::instance();
        $link = $purifier->purify($this->md->getName());
        if ($sort !== \null) {
            $link .= '&nbsp;' . $toogleIcon;
        }
        $href = '<a href="' . $url . '" title="' . $title . '">' . $link . '</a>';
        return $href;
    }
    public function initFromRequest($request)
    {
        $sortparam = $this->getSortParameter();
        if ($request->exist($sortparam)) {
            $this->setSort((int) $request->get($sortparam));
        }
    }
    public function _getMdHtml($item)
    {
        $mdHtml = \null;
        $md = $item->getMetadataFromLabel($this->md->getLabel());
        if ($md !== \null) {
            $mdHtml = \Docman_MetadataHtmlFactory::getFromMetadata($md, []);
        }
        return $mdHtml;
    }
    public function getTableBox($item, $view, $params)
    {
        $mdHtml = $this->_getMdHtml($item);
        if ($mdHtml !== \null) {
            return $mdHtml->getValue();
        }
        return '';
    }
    public function getJavascript($item, $view)
    {
        return '';
    }
}
