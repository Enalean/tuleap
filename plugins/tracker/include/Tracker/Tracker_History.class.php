<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/Codendi_Diff.class.php');

class Tracker_History {
    protected $artifact;
    public function __construct($artifact) {
        $this->artifact = $artifact;
    }
    
    protected function getDao() {
        return new Tracker_HistoryDao();
    }
    
    public function fetch() {
        $field_changes   = array();
        $comment_changes = array();
        $rows = array();
        foreach($this->getDao()->searchByid($this->artifact->id) as $row) {
            $rows[] = $row;
            if ($row['is_followup_comment']) {
                $comment_changes[$row['field_id']] = $row;
            } else {
                $field_changes[$row['field_id']] = $row;
            }
        }
        
        $first_changeset = $this->artifact->getFirstChangeset();
        $ff = Tracker_FormElementFactory::instance();
        
        $i = 0;
        $changes = array();
        $uh = UserHelper::instance();
        foreach($rows as $row) {
            if ($first_changeset->id != $row['changeset_id'] && ($row['is_followup_comment'] || $row['has_changed'])) {
                $id = 'tracker_history_'. $row['submitted_on'] .'_';
                $id .= $row['is_followup_comment'] ? 'comment' : '';
                $id .= $row['field_id'];
                
                $change = '<tr valign="top" class="'. html_get_alt_row_color($i++) .'" id="'. $id .'">';
                //date
                $change .= '<td>'. format_date('Y-m-d H:i:s', $row['submitted_on']) .'</td>';
                //user
                $change .= '<td>';
                if ($row['submitted_by']) {
                    $change .= $uh->getLinkOnUserFromUserId($row['submitted_by']);
                }
                $change .= '</td>';
                
                //field
                $change .= '<td>';
                if ($row['is_followup_comment']) {
                    $change .= '<a href="#followup_'. $row['changeset_id'] .'">Follow-up comment #'. $row['changeset_id'] .'</a>';
                } else {
                    if ($field = $ff->getFormElementById($row['field_id'])) {
                        $change .= $field->getLabel();
                    } else {
                        $change .= $row['field_id'];
                    }
                }
                $change .= '</td>';
                
                //changes
                $change .= '<td>';
                if ($row['is_followup_comment']) {
                    $previous = isset($comment_changes[$row['has_changed']]) ? $comment_changes[$row['has_changed']]['body'] : '';
                    $change .= $this->diff($previous, $row['body']);
                } else {
                    if ($field) {
                        $change .= $field->fetchHistory($this->artifact,
                                                        $this->artifact->getPreviousChangeset($row['changeset_id'])
                                                                       ->getValue($field),
                                                        $this->artifact->getChangeset($row['changeset_id'])
                                                                       ->getValue($field));
                    } else {
                        $change .= $row['value_id'];
                    }
                }
                $change .= '</td>';
                
                $change .= '</tr>';
                $changes[] = $change;
            }
        }
        return '<table><tbody>'. implode('', $changes) .'</tbody></table>';
    }
    
    protected function diff($before, $after) {
        $callback = array($this, '_filter_html_callback');
        $before = array_map($callback, explode("\n", $before));
        $after = array_map($callback, explode("\n", $after));
        $d = new Codendi_Diff($before, $after);
        $f = new Codendi_HtmlUnifiedDiffFormatter();
        $diff = $f->format($d);
        return $diff ? $diff : '<em>No changes</em>';
    }
    protected function _filter_html_callback($s) {
        $hp = Codendi_HTMLPurifier::instance();
        return  $hp->purify($s, CODENDI_PURIFIER_CONVERT_HTML);
    }
}
?>
