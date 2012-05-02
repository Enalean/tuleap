<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * 
 */
require_once('Docman_View_ItemDetailsSection.class.php');
require_once(dirname(__FILE__).'/../Docman_VersionFactory.class.php');
require_once(dirname(__FILE__).'/../Docman_ApprovalTableFactory.class.php');

class Docman_View_ItemDetailsSectionHistory extends Docman_View_ItemDetailsSection {
    var $logger;
    var $display_access_logs;
    function Docman_View_ItemDetailsSectionHistory(&$item, $url, $display_access_logs, &$logger) {
        parent::Docman_View_ItemDetailsSection($item, $url, 'history', $GLOBALS['Language']->getText('plugin_docman','details_history'));
        $this->logger =& $logger;
        $this->display_access_logs = $display_access_logs;
    }
    function getContent() {
        $content = '';
        $uh      = UserHelper::instance();
        if (is_a($this->item, 'Docman_File')) {
            $content .= '<h3>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions') .'</h3>';
            $version_factory =& new Docman_VersionFactory();
            $approvalFactory =& Docman_ApprovalTableFactory::getFromItem($this->item);
            if ($versions = $version_factory->getAllVersionForItem($this->item)) {
                if (count($versions)) {
                    $titles = array();
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_version');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_date');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_author');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_label');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_changelog');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_approval');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_delete_version');
                    $content .= html_build_list_table_top($titles, false, false, false);
                    $odd_even = array('boxitem', 'boxitemalt');
                    $i = 0;
                    foreach ($versions as $key => $nop) {
                        $download = Docman_View_View::buildUrl($this->url, array(
                            'action' => 'show',
                            'id'     => $this->item->getId(),
                            'version_number' => $versions[$key]->getNumber()
                        ));
                        $delete = Docman_View_View::buildUrl($this->url, array (
                            'action' =>'confirmDelete',
                            'id'     => $this->item->getId(),
                            'version' => $versions[$key]->getNumber()
                        ));
                        $user = $versions[$key]->getAuthorId() ? $uh->getDisplayNameFromUserId($versions[$key]->getAuthorId()) : $GLOBALS['Language']->getText('plugin_docman','details_history_anonymous');
                        $content .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                        $content .= '<td align="center"><a href="'. $download .'">'. $versions[$key]->getNumber() .'</a></td>';
                        $content .= '<td>'. html_time_ago($versions[$key]->getDate()) .'</td>';
                        $content .= '<td>'. $this->hp->purify($user)                                                  .'</td>';
                        $content .= '<td>'. $this->hp->purify($versions[$key]->getLabel())         .'</td>';
                        $content .= '<td>'. $this->hp->purify($versions[$key]->getChangelog(), CODENDI_PURIFIER_LIGHT) .'</td>';

                        $table = $approvalFactory->getTableFromVersion($versions[$key]);
                        if($table != null) {
                            $appTable = Docman_View_View::buildUrl($this->url, array(
                                'action' => 'details',
                                'section' => 'approval',
                                'id' => $this->item->getId(),
                                'version' => $versions[$key]->getNumber(),
                            ));
                            $content .= '<td align="center"><a href="'.$appTable.'">'.$titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_approval_show').'</a></td>';
                        } else {
                            $content .= '<td></td>';
                        }
                        $content .= '<td align="center"><a href="'.$delete.'"><img src="'.util_get_image_theme("ic/trash.png").'" height="16" width="16" border="0"></a></td>';
                        $content .= '</tr>';
                    }
                    $content .= '</table>';
                } else {
                    $content .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions_no') .'</div>';
                }
            } else {
                $content .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions_error') .'</div>';
            }
        }
        if ($this->logger) {
            $content .= $this->logger->fetchLogsForItem($this->item->getId(), $this->display_access_logs);
        }
        return $content;
    }
}
?>
