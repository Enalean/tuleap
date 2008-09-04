<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('Docman_View_ItemDetailsSection.class.php');
require_once(dirname(__FILE__).'/../Docman_VersionFactory.class.php');

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
        if (is_a($this->item, 'Docman_File')) {
            $content .= '<h3>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions') .'</h3>';
            $version_factory =& new Docman_VersionFactory();
            if ($versions = $version_factory->getAllVersionForItem($this->item)) {
                if (count($versions)) {
                    $titles = array();
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_version');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_date');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_author');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_label');
                    $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_changelog');
                    $content .= html_build_list_table_top($titles, false, false, false);
                    $odd_even = array('boxitem', 'boxitemalt');
                    $i = 0;
                    foreach ($versions as $key => $nop) {
                        $download = Docman_View_View::buildUrl($this->url, array(
                            'action' => 'show',
                            'id'     => $this->item->getId(),
                            'version_number' => $versions[$key]->getNumber()
                        ));
                        $user = $versions[$key]->getAuthorId() ? user_get_name_display_from_id($versions[$key]->getAuthorId()) : $GLOBALS['Language']->getText('plugin_docman','details_history_anonymous');
                        $content .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                        $content .= '<td><a href="'. $download .'">'. $versions[$key]->getNumber() .'</a></td>';
                        $content .= '<td>'. format_date($GLOBALS['sys_datefmt'], $versions[$key]->getDate()) .'</td>';
                        $content .= '<td>'. $user                                                  .'</td>';
                        $content .= '<td>'. $this->hp->purify($versions[$key]->getLabel())         .'</td>';
                        $content .= '<td>'. $this->hp->purify($versions[$key]->getChangelog(), CODEX_PURIFIER_BASIC) .'</td>';
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
