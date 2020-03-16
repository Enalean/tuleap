<?php
/**
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

class Docman_View_LoveDetails
{
    public $md;
    public $hp;

    public function __construct($md)
    {
        $this->md = $md;
        $this->hp = Codendi_HTMLPurifier::instance();
    }

    public function getNameField($value = '')
    {
        $html = '';

        $html .=  '<tr>';
        $html .=  '<td>' . dgettext('tuleap-docman', 'Name:') . '</td>';
        $html .=  '<td>';
        $html .=  '<input name="name" type="text" value="' . $this->hp->purify($value) . '" class="text_field" />';
        $html .=  '</td>';
        $html .=  '</tr>';

        return $html;
    }

    public function getDescriptionField($value = '')
    {
        $html = '';

        $html .=  '<tr>';
        $html .=  '<td>' . dgettext('tuleap-docman', 'Description:') . '</td>';
        $html .=  '<td>';
        $html .=  '<textarea name="descr">' . $this->hp->purify($value) . '</textarea>';
        $html .=  '</td>';
        $html .=  '</tr>';

        return $html;
    }

    public function getRankField($value = 'end')
    {
        $html = '';

        $html .=  '<tr>';
        $html .=  '<td>' . dgettext('tuleap-docman', 'Rank:') . '</td>';

        $vals = array('beg', 'end', '--');
        $texts = array(dgettext('tuleap-docman', 'At the beginning'),
                       dgettext('tuleap-docman', 'At the end'),
                       '----');
        $i = 3;

        $vIter = $this->md->getListOfValueIterator();
        $vIter->rewind();
        while ($vIter->valid()) {
            $e = $vIter->current();

            if ($e->getStatus() == 'A'
               || $e->getStatus() == 'P') {
                $vals[$i]  = $e->getRank() + 1;
                $texts[$i] = dgettext('tuleap-docman', 'After') . ' ' . Docman_MetadataHtmlList::_getElementName($e);
                $i++;
            }

            $vIter->next();
        }
        $html .=  '<td>';
        $html .=  html_build_select_box_from_arrays($vals, $texts, 'rank', $value, false, '');
        $html .=  '</td>';
        $html .=  '</tr>';

        return $html;
    }

    public function getHiddenFields($loveId = null)
    {
        $html = '';

        $html .= '<input type="hidden" name="md" value="' . $this->md->getLabel() . '" />';

        if ($loveId !== null) {
            $html .= '<input type="hidden" name="loveid" value="' . $loveId . '" />';
        }

        return $html;
    }
}
