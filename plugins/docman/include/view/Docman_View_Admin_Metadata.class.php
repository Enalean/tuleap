<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

require_once('Docman_View_Extra.class.php');

require_once(dirname(__FILE__).'/../Docman_MetaMetadataHtml.class.php');

class Docman_View_Admin_Metadata extends Docman_View_Extra {
    
    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_title') .'</h2>';
    }

    /**
     * List the available metadata
     */
    function getMetadataTable($mdIter, $groupId, $defaultUrl) {
        $content = '';

        $mdFactory = new Docman_MetadataFactory($groupId);

        $content .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_list_title').'</h3>'."\n";

        $content .= $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_instructions')."\n";
        
        $content .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_list_name'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_list_descr'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_list_status'),
                                                    $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_list_delete')
                                                    ));
        $altRowClass = 0;

        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();

            $canDelete = false;
            if($mdFactory->isRealMetadata($md->getLabel())) {
                $canDelete = true;
            }
            
            $trclass = html_get_alt_row_color($altRowClass++);
            $content .= '<tr class="'.$trclass.'">';

            $nameUrl  = $this->buildUrl($defaultUrl,
                                        array('action' => 'admin_md_details',
                                              'md'     => $md->getLabel()));
            $nameHref = '<a href="'.$nameUrl.'">'.$this->hp->purify($md->getName()).'</a>';
            $content .= '<td>'.$nameHref.'</td>';

            $content .= '<td>'.$this->hp->purify($md->getDescription()).'</td>';

            $content .= '<td>';
            if($md->isRequired()) {
                $content .= "-";
            }
            else {
                if($md->isUsed()) {
                    $content .= "Used";
                }
                else {
                    $content .= "Unused";
                }
            }
            $content .= '</td>';
            
            $trash = '-';
            if($canDelete) {
                $link = $this->buildUrl($defaultUrl, 
                                        array('action' => 'admin_delete_metadata',
                                              'md' => $md->getLabel()));
                
                $dfltlabel = '';//
                $warn = '';//$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_delete_warn', array($name, $dfltlabel));
                $alt  = '';//$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_val_delete_alt', array($name));
                $trash = html_trash_link($link, $warn, $alt);                    
            }
            $content .= '<td>'.$trash.'</td>';
            
            $content .= '</tr>'."\n";

            $mdIter->next();
        }

        $content .= '</table>'."\n";

        return $content;
    }

    /**
     * Return form to create a new metadata
     */
    function getNewMetadataForm($groupId) {
        $content = '';
        $content .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_new_title').'</h3>'."\n";

        $content .= '<form name="admin_create_metadata" method="post" action="?group_id='.$groupId.'&action=admin_create_metadata" class="docman_form">';

        $content .= '<table>';
        
        $md = new Docman_Metadata();       
        $md->setCanChangeName(true);
        $md->setCanChangeType(true);
        $md->setCanChangeDescription(true);
        $md->setCanChangeIsEmptyAllowed(true);
        $md->setIsEmptyAllowed(true);

        $sthCanChange = '';
        $metaMdHtml = new Docman_MetaMetadataHtml($md);        
        $content .= $metaMdHtml->getName($sthCanChange);
        $content .= $metaMdHtml->getDescription($sthCanChange);
        $content .= $metaMdHtml->getType($sthCanChange);
        $content .= $metaMdHtml->getEmptyAllowed($sthCanChange);
        $content .= $metaMdHtml->getUseIt($sthCanChange);
        
        $content .= '<tr>';
        $content .= '<td colspan="2">';
        $content .= '<input name="submit" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_new_submit').'" />';
        $content .= '</td>';
        $content .= '</tr>';

        $content .= '</table>';        

        $content .= '</form>';

        return $content;
    }

    /**
     * Import metadata from a given project
     */
    function getImportForm($groupId) {
        $content = '';
        $content .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_import_title').'</h3>'."\n";
        $content .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_import_desc').'</p>'."\n";
        $content .= '<form name="admin_import_metadata" method="post" action="?group_id='.$groupId.'&action=admin_import_metadata_check">';
        $content .= '<input name="import_group_id" type="text" value="" /><br />';
        $content .= '<input name="submit" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_import_submit').'" />';
        $content .= '</form>';
        return $content;
    }

    /**
     * Build page
     */
    function _content($params) {
        $content = '';
        
        $content .= $this->getMetadataTable($params['mdIter'], $params['group_id'], $params['default_url']);
        $content .= $this->getNewMetadataForm($params['group_id']);
        $content .= $this->getImportForm($params['group_id']);

        echo $content;
    }
}

?>
