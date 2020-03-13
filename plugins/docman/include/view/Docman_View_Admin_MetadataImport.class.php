<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('Docman_View_Extra.class.php');
require_once(dirname(__FILE__) . '/../Docman_MetadataComparator.class.php');

class Docman_View_Admin_MetadataImport extends Docman_View_Extra
{
    public $srcGo;
    public $dstGo;

    public function _title($params)
    {
        $pm = ProjectManager::instance();
        $this->srcGo = $pm->getProject($params['sSrcGroupId']);
        $this->dstGo = $pm->getProject($params['group_id']);

        echo '<h2>' . $this->_getTitle($params) . ' - ' . sprintf(dgettext('tuleap-docman', 'Import "%1$s" properties'), Codendi_HTMLPurifier::instance()->purify($this->srcGo->getPublicName())) . '</h2>';
    }


    public function getImportForm($sthToImport)
    {
        $html = '';
        if ($sthToImport) {
            $html .= '<form name="" method="post" action="?">';
            $html .= '<input type="hidden" name="action" value="admin_import_metadata">';
            $html .= '<input type="hidden" name="group_id" value="' . $this->dstGo->getGroupId() . '">';
            $html .= '<input type="hidden" name="plugin_docman_metadata_import_group" value="' . $this->srcGo->getGroupId() . '">';
            $html .= '<p>';
            $html .= '<input type="submit" name="confirm" value="' . dgettext('tuleap-docman', 'Import') . '">';
            $html .= ' ';
            $html .= '<input type="submit" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '">';
            $html .= '</p>';
            $html .= '</form>';
        } else {
            $html .= '<p>' . dgettext('tuleap-docman', 'The properties of the two projects are aligned. Nothing to do.') . '</p>';
        }
        return $html;
    }

    /**
     * Build page
     */
    public function _content($params)
    {
        $html = '';

        // True if there is sth to import in dst project.
        $sthToImport = false;

        $mdCmp = new Docman_MetadataComparator(
            $this->srcGo->getGroupId(),
            $this->dstGo->getGroupId(),
            $params['theme_path']
        );
        $html .= $mdCmp->getMetadataCompareTable($sthToImport);

        $html .= $this->getImportForm($sthToImport);

        echo $html;
    }
}
