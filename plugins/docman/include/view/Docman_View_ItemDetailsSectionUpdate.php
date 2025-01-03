<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

class Docman_View_ItemDetailsSectionUpdate extends Docman_View_ItemDetailsSectionActions
{
    public $validate;
    public $force;
    public $token;
    public function __construct($item, $url, $controller, $force, $token)
    {
        parent::__construct($item, $url, false, true, $controller);
        $this->force = $force;
        $this->token = $token;
    }

    public function getContent($params = [])
    {
        return $this->item->accept($this);
    }

    public function _updateHeader($enctype = '')
    {
        $content  = '';
        $content .= '<dl><dt>' . dgettext('tuleap-docman', 'Update') . '</dt><dd>';
        $content .= '<form action="' . $this->url . '&amp;id=' . $this->item->getId() . '" method="post" ' . $enctype . '>';
        return $content;
    }

    public function _updateFooter()
    {
        $content = '';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="' . $this->token . '" />';
        }
        $content .= '<input type="hidden" name="item[id]" value="' . $this->item->getId() . '" />';
        $content .= '<input type="hidden" name="action" value="update_wl" />';
        $content .= '<input type="submit" name="confirm" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        $content .= '<input type="submit" name="cancel"  value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';

        $content .= '</form>';

        $content .= '</dd></dl>';
        return $content;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): string
    {
        return '';
    }

    public function visitDocument(Docman_Document $item, array $params = []): string
    {
        $content = '';

        $content .= $this->_updateHeader();

        $fields   = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), ['force_item' => $this->force, 'request' => $this->_controller->request]);
        $content .= '<table>';
        foreach ($fields as $field) {
            $content .= '<tr style="vertical-align:top;"><td><label>' . $field->getLabel() . '</label></td><td>' . $field->getField() . '</td></tr>';
        }
        $content .= '</table>';

        $content .= $this->_updateFooter();

        return $content;
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink(Docman_Link $item, array $params = []): string
    {
        return $this->visitDocument($item, $params);
    }

    public function visitFile(Docman_File $item, array $params = []): string
    {
        return '';
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): string
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): string
    {
        $content = '';

        $enctype  = ' enctype="multipart/form-data"';
        $content .= $this->_updateHeader($enctype);

        // Fetch type selector
        $newView              = new Docman_View_NewDocument($this->_controller);
        $vparam               = [];
        $vparam['force_item'] = $item;
        $content             .= $newView->_getSpecificProperties($vparam);

        $content .= $this->_updateFooter();

        return $content;
    }
}
