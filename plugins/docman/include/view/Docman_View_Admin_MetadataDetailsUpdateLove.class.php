<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_MetadataDetailsUpdateLove extends Docman_View_Extra
{

    function _title($params)
    {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detupdlove_title', array($params['md']->getName(), Docman_MetadataHtmlList::_getElementName($params['love']))) .'</h2>';
    }

    function _content($params)
    {
        $md = $params['md'];
        $love = $params['love'];
        $html = '';

        //$html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detupdlove_title').'</h3>';

        $loveDetailsHtml = new Docman_View_LoveDetails($md);

        $act_url = DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_update_love'));

        $html .= '<form name="md_update_love" method="POST" action="'.$act_url.'" class="docman_form">';
        $html .= $loveDetailsHtml->getHiddenFields($love->getId());

        $html .= '<table>';
        $html .= $loveDetailsHtml->getNameField($love->getName());
        $html .= $loveDetailsHtml->getDescriptionField($love->getDescription());
        $html .= $loveDetailsHtml->getRankField('--');
        $html .= '</table>';

        $html .= '<input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detupdlove_update').'" />';

        $html .= '</form>';

        $backUrl  = DocmanViewURLBuilder::buildUrl(
            $params['default_url'],
            array('action' => 'admin_md_details',
            'md' => $md->getLabel())
        );
        $html .= '<p><a href="'.$backUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detupdlove_backtomenu').'</a></p>';

        echo $html;
    }
}
