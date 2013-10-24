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

require_once('common/include/Codendi_HTTPPurifier.class.php');

class Tracker_Report_Renderer_Table extends Tracker_Report_Renderer implements Tracker_Report_Renderer_ArtifactLinkable {
    
    public $chunksz;
    public $multisort;
    
    /**
     * Constructor
     *
     * @param int $id the id of the renderer
     * @param Report $report the id of the report
     * @param string $name the name of the renderer
     * @param string $description the description of the renderer
     * @param int $rank the rank
     * @param int $chnuksz the size of the chunk (Browse X at once)
     * @param bool $multisort use multisort?
     */
    public function __construct($id, $report, $name, $description, $rank, $chunksz, $multisort) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->chunksz   = $chunksz;
        $this->multisort = $multisort;
    }
    
    public function initiateSession() {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.chunksz",   $this->chunksz);
        $this->report_session->set("{$this->id}.multisort", $this->multisort);
    }
    
    /**
     * Delete the renderer
     */
    public function delete() {
        $this->getSortDao()->delete($this->id);
        $this->getColumnsDao()->delete($this->id);
        $this->getAggregatesDao()->deleteByRendererId($this->id);
    }
    
    protected $_sort;
    /**
     * @param array $sort
     */
    public function setSort($sort) {
        $this->_sort = $sort;
    }
    /**
     * Get field ids used to (multi)sort results
     * @return array [{'field_id' => 12, 'is_desc' => 0, 'rank' => 2}, [...]]
     */
    public function getSort($store_in_session = true) {
        $sort = null;
        if ($store_in_session) {
            if (isset($this->report_session)) {
                $sort = $this->report_session->get("{$this->id}.sort");
            }
        }
        
        if ( $sort ) {
                $ff = $this->report->getFormElementFactory();
                foreach ($sort as $field_id => $properties) {
                    if ($properties) {
                        if ($field = $ff->getFormElementById($field_id)) {
                            if ($field->userCanRead()) {
                                $this->_sort[$field_id] = array(
                                       'renderer_id '=> $this->id,
                                       'field_id'    => $field_id,
                                       'is_desc'     => $properties['is_desc'],
                                       'rank'        => $properties['rank'],
                                    );
                                $this->_sort[$field_id]['field'] = $field;
                            }
                        }
                    }
                }
        } else if (!isset($this->report_session) || !$this->report_session->hasChanged()){
            
            if (!is_array($this->_sort)) {
                $ff = $this->getFieldFactory();
                $this->_sort = array();
                foreach($this->getSortDao()->searchByRendererId($this->id) as $row) {
                    if ($field = $ff->getUsedFormElementById($row['field_id'])) {
                        if ($field->userCanRead()) {
                            $this->_sort[$row['field_id']] = $row;
                            $this->_sort[$row['field_id']]['field'] = $field;
                        }
                    }
                }
            }
            $sort = $this->_sort;
            if ($store_in_session) {
                foreach($sort as $field_id => $properties) {
                    $this->report_session->set("{$this->id}.sort.{$field_id}.is_desc", $properties['is_desc']);
                    $this->report_session->set("{$this->id}.sort.{$field_id}.rank", $properties['rank']);
                }
            }
        } else {
            $this->_sort = array();
        }
        return $this->_sort;
    }
    /**
     * Adds sort values to database
     * 
     * @param array $sort
     */
    public function saveSort($sort) {
        $dao = $this->getSortDao();
        foreach ($sort as $key => $s) {
            $dao->create($this->id, $s['field']->id);
        }
    }
    
    protected $_columns;
    /**
     * @param array $cols 
     */
    public function setColumns($cols) {
        $this->_columns = $cols;
    }
    /**
     * Adds columns to database
     * 
     * @param array $cols
     */
    public function saveColumns($cols) {
        $dao = $this->getColumnsDao();
        $rank = -1;
        foreach ($cols as $key => $col) {
            $rank ++;
            $dao->create($this->id, $col['field']->id, null, $rank);
        }
    }
    
    /**
     * Get field ids and width used to display results
     * @return array  [{'field_id' => 12, 'width' => 33, 'rank' => 5}, [...]]
     */
    public function getColumns() {
        $session_renderer_table_columns = null;
        if (isset($this->report_session)) {
            $session_renderer_table_columns = $this->report_session->get("{$this->id}.columns");
        }
        if ( $session_renderer_table_columns ) {            
                $columns = $session_renderer_table_columns;
                $ff = $this->report->getFormElementFactory();
                $this->_columns = array();
                foreach ($columns as $key => $column) {
                    if ($formElement = $ff->getFormElementFieldById($key)) {
                        if ($formElement->userCanRead()) {
                            $this->_columns[$key] = array(
                                'field'    => $formElement,
                                'field_id' => $key,
                                'width'    => $column['width'],
                                'rank'     => $column['rank'],
                                );
                        }
                    }
                }
        } else {
            if (empty($this->_columns)) {
                $this->_columns = $this->getColumnsFromDb();
            }
        }
        return $this->_columns;
    }
    
    protected $_aggregates;
    /**
     * @param array $aggs 
     */
    public function setAggregates($aggs) {
        $this->_aggregates = $aggs;
    }
    /**
     * Adds aggregates to database
     * 
     * @param array $cols
     */
    public function saveAggregates($aggs) {
        $dao = $this->getAggregatesDao();
        foreach ($aggs as $field_id => $aggregates) {
            foreach ($aggregates as $aggregate) {
                $dao->create($this->id, $field_id, $aggregate);
            }
        }
    }
    public function getAggregates() {
        $session_renderer_table_functions = &$this->report_session->get("{$this->id}.aggregates");
        if ( $session_renderer_table_functions ) {
            $aggregates = $session_renderer_table_functions;
            $ff = $this->report->getFormElementFactory();
            foreach ($aggregates as $field_id => $aggregates) {
                if ($formElement = $ff->getFormElementById($field_id)) {
                    if ($formElement->userCanRead()) {
                        $this->_aggregates[$field_id] = $aggregates;
                    }
                }
            }
        } else {
            if (empty($this->_aggregates)) {
                $ff = $this->getFieldFactory();
                $this->_aggregates = array();
                foreach($this->getAggregatesDao()->searchByRendererId($this->id) as $row) {
                    if ($field = $ff->getUsedFormElementById($row['field_id'])) {
                        if ($field->userCanRead()) {
                            if (!isset($this->_aggregates[$row['field_id']])) {
                                $this->_aggregates[$row['field_id']] = array();
                            }
                            $this->_aggregates[$row['field_id']][] = $row;
                        }
                    }
                }
            }
            $aggregates = $this->_aggregates;
            foreach($aggregates as $field_id => $agg) {
                $this->report_session->set("{$this->id}.aggregates.{$field_id}", $agg);
            }
        
        }       
        return $this->_aggregates;
    }

    public function storeColumnsInSession() {
        $columns = $this->_columns;
        foreach($columns as $key => $column) {
            $this->report_session->set("{$this->id}.columns.{$key}.width", isset($column['width']) ? $column['width'] : 0);
            $this->report_session->set("{$this->id}.columns.{$key}.rank", isset($column['rank']) ? $column['rank'] : 0);
        }
    }
    
     /**
     * Get field ids and width used to display results
     * @return array  [{'field_id' => 12, 'width' => 33, 'rank' => 5}, [...]]
     */
    public function getColumnsFromDb() {
        $ff = $this->getFieldFactory();
        $this->_columns = array();
        foreach($this->getColumnsDao()->searchByRendererId($this->id) as $row) {
            if ($field = $ff->getUsedFormElementFieldById($row['field_id'])) {
                if ($field->userCanRead()) {
                    $this->_columns[$row['field_id']] = $row;
                    $this->_columns[$row['field_id']]['field'] = $field;
                }
            }
        }
        return $this->_columns;
    }
    
    protected function getSortDao() {
        return new Tracker_Report_Renderer_Table_SortDao();
    }
    
    protected function getColumnsDao() {
        return new Tracker_Report_Renderer_Table_ColumnsDao();
    }
    
    protected function getAggregatesDao() {
        return new Tracker_Report_Renderer_Table_FunctionsAggregatesDao();
    }
    
    /**
     * Fetch content of the renderer
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified, PFUser $user) {
        $html = '';
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset     = (int)$request->get('offset');
        if ($offset < 0) {
            $offset = 0;
        }
        if($request->get('renderer')) {
            $renderer_data = $request->get('renderer');
            if ( isset($renderer_data[$this->id]) && isset($renderer_data[$this->id]['chunksz'])) {
                $this->report_session->set("{$this->id}.chunksz", $renderer_data[$this->id]['chunksz']);
                $this->report_session->setHasChanged();
                $this->chunksz = $renderer_data[$this->id]['chunksz'];
            }
        }
        
        $extracolumn = self::EXTRACOLUMN_MASSCHANGE;
        if ((int)$request->get('link-artifact-id')) {
            $extracolumn = self::EXTRACOLUMN_LINK;
        }
            
        
        //Display # matching
        $html .= $this->_fetchMatchingNumber($total_rows);
        
        //Display sort info
        if ($report_can_be_modified) {
            $html .= $this->_fetchSort();
        }
        
        if ($report_can_be_modified && $this->report->userCanUpdate($user)) {
            //Display the column switcher
            $html .= $this->_fetchAddColumn();
        }
        
        //Display the head of the table
        if ($report_can_be_modified) {
            $only_one_column = null;
            $with_sort_links = true;
        } else {
            $only_one_column = null;
            $with_sort_links = false;
        }
        $html .= $this->_fetchHead($extracolumn, $only_one_column, $with_sort_links);
        
        //Display the body of the table
        $html .= $this->_fetchBody($matching_ids, $total_rows, $offset, $extracolumn);
        
        //Display next/previous
        $html .= $this->_fetchNextPrevious($total_rows, $offset, $report_can_be_modified, (int)$request->get('link-artifact-id'));
        
        //Display masschange controls
        if ((int)$request->get('link-artifact-id')) {
            //TODO
        } else {
            $html .= $this->_fetchMassChange($matching_ids, $total_rows, $offset);
        }
        
        return $html;
    }
    
    /**
     * Fetch content of the renderer
     * @return string
     */
    public function fetchAsArtifactLink($matching_ids, $field_id, $read_only, $prefill_removed_values, $only_rows = false, $from_aid = null) {
        $html = '';
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset     = 0;
        $use_data_from_db = true;
        $extracolumn     = $read_only ? self::NO_EXTRACOLUMN : self::EXTRACOLUMN_UNLINK;
        $with_sort_links = false;
        $only_one_column = null;
        $pagination      = false;
        $read_only       = true;
        $store_in_session = true;
        $head = '';

        //Display the head of the table
        $suffix = '_'. $field_id .'_'. $this->report->id .'_'. $this->id;
        $head .= $this->_fetchHead($extracolumn, $only_one_column, $with_sort_links, $use_data_from_db, $suffix);
        if (!$only_rows) {
            $html .= $head;
        }
        //Display the body of the table
        $html .= $this->_fetchBody($matching_ids, $total_rows, $offset, $extracolumn, $only_one_column, $use_data_from_db, $pagination, $field_id, $prefill_removed_values, $only_rows, $read_only, $store_in_session, $from_aid);
        
        if (!$only_rows) {
            $html .= $this->fetchArtifactLinkGoToTracker();
        }
        
        if ($only_rows) {
            return array('head' => $head, 'rows' => $html);
        }
        return $html;
    }
    
    /**
     * Get the item of the menu options. 
     *
     * If no items is returned, the menu won't be displayed.
     *
     * @return array of 'item_key' => {url: '', icon: '', label: ''}
     */
    public function getOptionsMenuItems() {
        $items = parent::getOptionsMenuItems();
        $items['export_light'] = array(
            'url'   => TRACKER_BASE_URL.'/?'.http_build_query(
                array(
                    'report'         => $this->report->id,
                    'renderer'       => $this->id,
                    'func'           => 'renderer',
                    'renderer_table' => array(
                        'export'                       => 1,
                        'export_only_displayed_fields' => 1,
                    ),
                )
            ),
            'icon'  => $GLOBALS['HTML']->getImage('ic/clipboard-paste.png', array('border' => 0, 'alt' => '', 'style' => 'vertical-align:middle;')),
            'label' => $GLOBALS['Language']->getText('plugin_tracker_include_report' ,'export_only_report_columns'),
        );
        $items['export_full'] = array(
            'url'   => TRACKER_BASE_URL.'/?'.http_build_query(
                array(
                    'report'         => $this->report->id,
                    'renderer'       => $this->id,
                    'func'           => 'renderer',
                    'renderer_table' => array(
                        'export'                       => 1,
                        'export_only_displayed_fields' => 0,
                    ),
                )
            ),
            'icon'  => $GLOBALS['HTML']->getImage('ic/clipboard-paste.png', array('border' => 0, 'alt' => '', 'style' => 'vertical-align:middle;')),
            'label' => $GLOBALS['Language']->getText('plugin_tracker_include_report' ,'export_all_columns'),
        );
        return $items;
    }
    
    protected function _form($id = '', $func = 'renderer') {
        $html  = '';
        $html .= '<form method="POST" action="" id="'. $id .'">';
        $html .= '<input type="hidden" name="report" value="'. $this->report->id .'" />';
        $html .= '<input type="hidden" name="renderer" value="'. $this->id .'" />';
        $html .= '<input type="hidden" name="func" value="'.$func.'" />';
        return $html;
    }
    
    /**
     * Fetch content to be displayed in widget
     */
    public function fetchWidget(PFUser $user) {
        $html = '';
        $use_data_from_db = true;
        $store_in_session = false;
        $matching_ids = $this->report->getMatchingIds(null, $use_data_from_db);
        $total_rows   = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset = 0;
        $extracolumn            = self::NO_EXTRACOLUMN;
        $with_sort_links        = false;
        $only_one_column        = null;
        $pagination             = true;
        $artifactlink_field_id  = null;
        $prefill_removed_values = null;
        $only_rows              = false;
        $read_only              = true;
        $id_suffix              = '';
        //Display the head of the table
        $html .= $this->_fetchHead($extracolumn, $only_one_column, $with_sort_links, $use_data_from_db, $id_suffix, $store_in_session);
        //Display the body of the table
        $html .= $this->_fetchBody($matching_ids, $total_rows, $offset, $extracolumn, $only_one_column, $use_data_from_db, $pagination, $artifactlink_field_id, $prefill_removed_values, $only_rows, $read_only, $store_in_session);
        
        //Dispaly range
        $offset_last = min($offset + $this->chunksz - 1, $total_rows - 1);
        $html .= '<div id="tracker_report_table_pager" class="tracker_report_table_pager">';
        $html .= $this->_fetchRange($offset + 1, $offset_last + 1, $total_rows);
        
        $html .= $this->fetchWidgetGoToReport();
        
        $html .= '</div>';
        
        return $html;
    }
    
    protected function _fetchMatchingNumber($total_rows) {
        $html = '<h3>'. $total_rows .' '. $GLOBALS['Language']->getText('plugin_tracker_include_report','matching') .'</h3>';
        return $html;
    }
    
    protected function _fetchSort() {
        $html = '';
        $html .= '<div id="tracker_report_table_sortby_panel">';
        $sort_columns = $this->getSort();
        if ($this->sortHasUsedField()) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_report','sort_by');
            $ff = $this->getFieldFactory();
            $sort = array();
            foreach($sort_columns as $row) {
                if ($row['field'] && $row['field']->isUsed()) {
                    $sort[] = '<a id="tracker_report_table_sort_by_'. $row['field_id'] .'"
                                  href="?' . 
                            http_build_query(array(
                                                   'report'                  => $this->report->id,
                                                   'renderer'                => $this->id,
                                                   'func'                    => 'renderer',
                                                   'renderer_table[sort_by]' => $row['field_id'],
                                                  )
                            ) . '">' . 
                            $row['field']->getLabel() .
                            $GLOBALS['HTML']->getImage(($row['is_desc'] ? 'dn' : 'up') .'_arrow.png') .
                            '</a>';
                }
            }
            $html .= implode(' &gt; ', $sort);
            
            $html .= '<div class="tracker_report_table_sort_controls">';
            //reset sort
            $html .= '<a href="?' . http_build_query(array(
                               'report'                    => $this->report->id,
                               'renderer'                  => $this->id,
                               'func'                      => 'renderer',
                               'renderer_table[resetsort]' => 1
                               )) .'">'. 
                     $GLOBALS['Language']->getText('plugin_tracker_report','reset_sort') .
                     '</a>';
            $html .= ' | ';
            //toggle multisort
            $multisort_label = $this->multisort ? $GLOBALS['Language']->getText('plugin_tracker_report','disable_multisort') : $GLOBALS['Language']->getText('plugin_tracker_report','enable_multisort');
            $html .= '<a href="?' . http_build_query(array(
                               'report'                    => $this->report->id,
                               'renderer'                  => $this->id,
                               'func'                      => 'renderer',
                               'renderer_table[multisort]' => 1
                               )) .'"';;            
            $html .= '>'. $multisort_label .'</a>';
            $html .= '</div>';
        } else {
            $html .= '<span style="color:#666">'. $GLOBALS['Language']->getText('plugin_tracker_report', 'click_to_sort') .'</span>';
        }
        $html .= '</div>';
        return $html;
    }
    
    protected function _fetchAddColumn() {
        $html = '';
        $used = $this->getColumns();
        $options = '';
        foreach($this->report->getTracker()->getFormElements() as $formElement) {
            if ($formElement->userCanRead()) {
                $options .= $formElement->fetchAddColumn($used);
            }
        }
        if ($options) {
            $html .= $this->_form('tracker_report_table_addcolumn_form');
            $html .= '<div id="tracker_report_table_addcolumn_panel">';
            $html .= '<select name="renderer_table[add_column]" id="tracker_report_table_add_column" autocomplete="off">';
            $html .= '<option selected="selected" value="">'. '-- '.$GLOBALS['Language']->getText('plugin_tracker_report', 'toggle_columns').'</option>';
            $html .= $options;
            $html .= '</select>';
            $html .= '<noscript><input type="submit" value="Add !" /></noscript>';
            $html .= '</div>';
            $html .= '</form>';
        }
        return $html;
    }
    
    protected function _fetchRange($from, $to, $total_rows) {
        $html = '';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_report','items');
        $html .= ' <b>'. $from .' - '. $to .'</b>';
        $html .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_renderer_table','items_range_of') . ' <b>'. $total_rows .'</b>';
        $html .= '. ';
        return $html;
    }
    
    protected function _fetchNextPrevious($total_rows, $offset, $report_can_be_modified, $link_artifact_id = null) {
        $html = '';
        if ($total_rows) {
            $parameters = array(
                'report'   => $this->report->id,
                'renderer' => $this->id,
            );
            if ($link_artifact_id) {
                $parameters['link-artifact-id'] = (int)$link_artifact_id;
                $parameters['only-renderer']    = 1;
            }
            //offset should be the last parameter to ease the concat later
            $parameters['offset'] = '';
            $url = '?'. http_build_query($parameters);
            
            $chunk = $GLOBALS['Language']->getText('global','btn_browse');
            if ($report_can_be_modified) {
                $chunk .= ' <input id="renderer_table_chunksz_input" type="text" name="renderer_table[chunksz]" size="1" maxlength="5" value="'. (int)$this->chunksz.'" />';
                $chunk .= ' <input type="submit" id="renderer_table_chunksz_btn" value="Ok" /> ';
            } else {
                $chunk .= ' '. (int)$this->chunksz .' ';
            }
            $chunk .= $GLOBALS['Language']->getText('plugin_tracker_include_report','at_once');
            
            
            $html .= $this->_form('tracker_report_table_next_previous_form');
            if ($total_rows < $this->chunksz) {
                $html .= '<div id="tracker_report_table_pager" class="tracker_report_table_pager" style="text-align:center" class="small">';
                $html .= $this->_fetchRange(1, $total_rows, $total_rows);
                $html .= $chunk;
                $html .= '</div>';
            } else {
                $html .= '<table id="tracker_report_table_pager" class="tracker_report_table_pager" width="100%"><tr>';
                $html .= '<td align="center" class="small">';
                if ($offset > 0) {
                    $html .= '<a href="'.$url . 0 .'" class="small"><b>&lt;&lt;&nbsp;';
                    $html .= $GLOBALS['Language']->getText('global','begin');
                    $html .= '</b></a>';
                    $html .= '&nbsp &nbsp;';
                    $html .= '<a href="'.$url . ($offset - $this->chunksz) .'" class="small"><b>&lt;&nbsp;';
                    $html .= $GLOBALS['Language']->getText('global','prev') .'</b></a>';
                } else {
                    $html .= '<span class="disable">&lt;&lt;&nbsp;';
                    $html .= $GLOBALS['Language']->getText('global','begin');
                    $html .= '&nbsp; &lt;&nbsp;';
                    $html .= $GLOBALS['Language']->getText('global','prev');
                    $html .= '</span>';
                }
                
                $offset_last = min($offset + $this->chunksz - 1, $total_rows - 1);
                $html .= '<span class="tracker_report_table_pager_range">';
                $html .= $this->_fetchRange($offset + 1, $offset_last + 1, $total_rows);
                $html .= $chunk;
                $html .= '</span>';
                
                if (($offset + $this->chunksz) < $total_rows) {
                    if ($this->chunksz > 0) {
                        $offset_end = ($total_rows - ($total_rows % $this->chunksz));
                    } else {
                        $offset_end = PHP_INT_MAX; //weird! it will take many steps to reach the last page if the user is browsing 0 artifacts at once
                    }
                    if ($offset_end >= $total_rows) { 
                        $offset_end -= $this->chunksz; 
                    }
                    $html .= '<a href="'.$url . ($offset + $this->chunksz) .'" class="small"><b>';
                    $html .= $GLOBALS['Language']->getText('global','next');
                    $html .= '&nbsp;&gt;</b></a>';
                    $html .= '&nbsp;&nbsp;&nbsp;';
                    $html .= '<a href="'.$url . $offset_end .'" class="small"><b>';
                    $html .= $GLOBALS['Language']->getText('global','end');
                    $html .= '&nbsp;&gt;&gt;</b></a>';
                } else {
                    $html .= '<span class="disable">';
                    $html .= $GLOBALS['Language']->getText('global','next');
                    $html .= '&nbsp;&gt;&nbsp;&nbsp; ';
                    $html .= $GLOBALS['Language']->getText('global','end');
                    $html .= '&nbsp;&gt;&gt;</span>';
                }
                $html .= '</td>';
                $html .= '</tr></table>';
            }
            $html .= '</form>';
        }
        return $html;
    }
    
    protected function reorderColumnsByRank($columns) {
        
        $array_rank = array();
        foreach($columns as $field_id => $properties) {
            $array_rank[$field_id] = $properties['rank'];
        }
        asort($array_rank);        
        $columns_sort = array();
        foreach ($array_rank as $id => $rank) {
            $columns_sort[$id] = $columns[$id];
        }
        return $columns_sort;
    }

    const NO_EXTRACOLUMN         = 0;
    const EXTRACOLUMN_MASSCHANGE = 1;
    const EXTRACOLUMN_LINK       = 2;
    const EXTRACOLUMN_UNLINK     = 3;
    
    protected function _fetchHead($extracolumn = 1, $only_one_column = null, $with_sort_links = true, $use_data_from_db = false, $id_suffix = '', $store_in_session = true) {
        $html = '';
        $html .= '<table';
        if (!$only_one_column) {
            $html .= ' id="tracker_report_table'. $id_suffix .'"  width="100%" cellpadding="2" cellspacing="1" border="0"';
        }
        if ($with_sort_links && $this->report->userCanUpdate(UserManager::instance()->getCurrentUser())) {
            $html .= ' class="reorderable resizable"';
        }
        $html .= '>';
        $html .= '<thead>';
        $html .= '<tr class="boxtable">';
        
        $current_user = UserManager::instance()->getCurrentUser();
        
        if ($extracolumn) {
            $display_extracolumn = true;
            $classname = 'tracker_report_table_';
            if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                $classname .= 'masschange';
            } else if ($extracolumn === self::EXTRACOLUMN_LINK) {
                $classname .= 'link';
            } else if ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                $classname .= 'unlink';                
            } else {
                $display_extracolumn = false;
            }
            
            if ($display_extracolumn) {
                $html .= '<th class="boxtitle '. $classname .'">&nbsp;</th>';
            }
        }
        
        //the link to the artifact
        if (!$only_one_column) {
            $html .= '<th class="boxtitle" width="16px">&nbsp;</th>';
        }
        
        $ff = $this->getFieldFactory();
        $url = '?'. http_build_query(array(
                                           'report'                  => $this->report->id,
                                           'renderer'                => $this->id,
                                           'func'                    => 'renderer',
                                           'renderer_table[sort_by]' => '',
                                          )
        );
        if ($use_data_from_db) {
            $all_columns = $this->reorderColumnsByRank($this->getColumnsFromDb());
        } else {
            $all_columns = $this->reorderColumnsByRank($this->getColumns());
        }
        if ($only_one_column) {
            if (isset($all_columns[$only_one_column])) {
                $columns = array($all_columns[$only_one_column]);
            } else {
                $columns = array(array(
                    'width' => 0,
                    'field' => $ff->getUsedFormElementById($only_one_column),
                ));
            }
        } else {
            $columns = $all_columns;
        }
        $sort_columns = $this->getSort($store_in_session);
        
        $i = count($columns);
        foreach($columns as $column) {
            if ($column['width']) {
                $width = 'width="'.$column['width'].'%"';
            } else {
                $width = '';
            }
            if ( !empty($column['field']) && $column['field']->isUsed()) {
                $html .= '<th class="boxtitle tracker_report_table_column" 
                              id="tracker_report_table_column_'. $column['field']->id .'" 
                              '. $width .'>';
                $html .= '<input type="hidden" 
                                 id="tracker_report_table_column_'. $column['field']->id .'_parent" 
                                 value="'. $column['field']->parent_id .'" />';
                                 
                $label = $column['field']->getLabel();
                if (isset($sort_columns[$column['field']->getId()])) {
                    $label .= ' '.$GLOBALS['HTML']->getImage(($sort_columns[$column['field']->getId()]['is_desc'] ? 'dn' : 'up') .'_arrow.png');
                }
                
                if ($with_sort_links) {
                    $html .= '<a href="'. $url . $column['field']->id .'" class="tracker_report_table_column_title"><span>';
                    $html .= $label;
                    $html .= '</span></a>';
                } else {
                    $html .= $label;
                }
                $html .= '</th>';
            }
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }

    public function getTableColumns($only_one_column, $use_data_from_db, $store_in_session = true) {
        $columns = array();
        if ($use_data_from_db) {
            $all_columns = $this->reorderColumnsByRank($this->getColumnsFromDb());
        } else {
            $all_columns = $this->reorderColumnsByRank($this->getColumns());
        }
        if ($only_one_column) {
            if (isset($all_columns[$only_one_column])) {
                $columns = array($all_columns[$only_one_column]);
            } else {
                $columns = array(array(
                    'width' => 0,
                    'field' => $this->getFieldFactory()->getUsedFormElementFieldById($only_one_column),
                ));
            }
        } else {
            $columns = $all_columns;
        }
        return $columns;
    }
    
    /**
     * Display the body of the table
     *
     * @param array $matching_ids           The matching ids to display array('id' => '"1,4,8,10", 'last_matching_ids' => "123,145,178,190")
     * @param int   $total_rows             The number of total rows (pagination powwwa)
     * @param int   $offset                 The offset of the pagination
     * @param int   $extracolumn            Need for an extracolumn? NO_EXTRACOLUMN | EXTRACOLUMN_MASSCHANGE | EXTRACOLUMN_LINK | EXTRACOLUMN_UNLINK. Default is EXTRACOLUMN_MASSCHANGE.
     * @param int   $only_one_column        The column (field_id) to display. null if all columns are needed. Default is null
     * @param bool  $use_data_from_db       true if we need to retrieve data from the db instead of the session. Default is false.
     * @param bool  $pagination             true if we display the pagination. Default is true.
     * @param int   $artifactlink_field_id  The artifactlink field id. Needed to display report in ArtifactLink field. Default is null
     * @param array $prefill_removed_values Array of artifact_id to pre-check. array(123 => X, 345 => X, ...). Default is null
     * @param bool  $only_rows              Display only rows, no aggregates or stuff like that. Default is false.
     * @param bool  $read_only              Display the table in read only mode. Default is false.
     *
     * @return string html
     */
    protected function _fetchBody($matching_ids, $total_rows, $offset, $extracolumn = 1, $only_one_column = null, $use_data_from_db = false, $pagination = true, $artifactlink_field_id = null, $prefill_removed_values = null, $only_rows = false, $read_only = false, $store_in_session = true, $from_aid = null) {
        $html = '';
        if (!$only_rows) {
            $html .= "\n<!-- table renderer body -->\n";
            $html .= '<tbody>';
            $additional_classname = '';
        } else {
            $additional_classname = 'additional';
        }
        if ($total_rows) {
           
            $columns = $this->getTableColumns($only_one_column, $use_data_from_db);

            $extracted_fields = $this->extractFieldsFromColumns($columns);
            
            $aggregates = false;
            
            $queries = $this->buildOrderedQuery($matching_ids, $extracted_fields, $aggregates, $store_in_session);
            
            $dao = new DataAccessObject();
            $results = array();
            foreach($queries as $sql) {
                //Limit
                if ($total_rows > $this->chunksz && $pagination) {
                    $sql .= " LIMIT ". (int)$offset .", ". (int)$this->chunksz;
                }
                $results[] = $dao->retrieve($sql);
            }
            // test if first result is valid (if yes, we consider that others are valid too)
            if (!empty($results[0])) {
                $i = 0;
                //extract the first results
                $first_result = array_shift($results);
                //loop through it
                foreach ($first_result as $row) { //id, f1, f2
                    //merge the row with the other results
                    foreach ($results as $result) {
                        //[id, f1, f2] + [id, f3, f4]
                        $row = array_merge($row, $result->getRow());
                        //row == id, f1, f2, f3, f4...
                    }
                    $html .= '<tr class="'. html_get_alt_row_color($i++) .' '. $additional_classname .'">';
                    $current_user = UserManager::instance()->getCurrentUser();
                    if ($extracolumn) {
                        $display_extracolumn = true;
                        $checked   = '';
                        $classname = 'tracker_report_table_';
                        if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                            $classname .= 'masschange';
                            $name       = 'masschange_aids';
                        } else if ($extracolumn === self::EXTRACOLUMN_LINK) {
                            $classname .= 'link';
                            $name       = 'link-artifact[search]';
                        } else if ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                            $classname .= 'unlink';
                            $name       = 'artifact['. (int)$artifactlink_field_id .'][removed_values]['. $row['id'] .']';
                            if (isset($prefill_removed_values[$row['id']])) {
                                $checked = 'checked="checked"';
                            }
                        } else {
                            $display_extracolumn = false;
                        }
                        
                        if ($display_extracolumn) {
                            $html .= '<td class="'. $classname .'" width="1">';
                            $html .= '<span><input type="checkbox" name="'. $name .'[]" value="'. $row['id'] .'" '. $checked .' /></span>';
                            $html .= '</td>';
                        }
                    }
                    if (!$only_one_column) {
                        if ($from_aid != null) {
                            $html .= '<td style="white-space:nowrap"><a class="direct-link-to-artifact icon-eye-open" href="'.TRACKER_BASE_URL.'/?aid='. $row['id'] .'&from_aid='.$from_aid.'" title="'.
                                $GLOBALS['Language']->getText('plugin_tracker_include_report','show')
                                .' artifact #'. $row['id'] .'"></a>';
                            $html .= '&nbsp;&nbsp;&nbsp;<a class="direct-link-to-artifact icon-edit" href="'.TRACKER_BASE_URL.'/?aid='. $row['id'] .'&from_aid='.$from_aid.'&func=edit" title="'.
                                $GLOBALS['Language']->getText('plugin_tracker_include_report','edit')
                                .' artifact #'. $row['id'] .'"></a></td>';
                        } else {
                            $html .= '<td style="white-space:nowrap"><a class="direct-link-to-artifact icon-eye-open" href="'.TRACKER_BASE_URL.'/?aid='. $row['id'] .'" title="'.
                                $GLOBALS['Language']->getText('plugin_tracker_include_report','show')
                                .' artifact #'. $row['id'] .'"></a>';
                            $html .= '&nbsp;&nbsp;&nbsp;<a class="direct-link-to-artifact icon-edit" href="'.TRACKER_BASE_URL.'/?aid='. $row['id'] .'&func=edit" title="'.
                                $GLOBALS['Language']->getText('plugin_tracker_include_report','edit')
                                .' artifact #'. $row['id'] .'"></a></td>';
                        }
                    }
                    foreach($columns as $column) {
                        if($column['field']->isUsed()) {
                            $field_name = $column['field']->name;
                            $value      = isset($row[$field_name]) ? $row[$field_name] : null;
                            $html      .= '<td class="tracker_report_table_column_'. $column['field']->id .'">';
                            $html      .= $column['field']->fetchChangesetValue($row['id'], $row['changeset_id'], $value, $from_aid);
                            $html      .= '</td>';
                        }
                    }
                    $html .= '</tr>';
                }
                if (!$only_rows) {
                    $html .= $this->fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $extracted_fields, $use_data_from_db, $read_only);
                }
            }
        } else {
            $html .= '<tr class="tracker_report_table_no_result"><td colspan="'. (count($this->getColumns())+2) .'" align="center">'. 'No results' .'</td></tr>';
        }
        if (!$only_rows) {
            $html .= '</tbody>';
            $html .= '</table>';
        }
        return $html;
    }
    
    public function fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $extracted_fields, $use_data_from_db, $read_only) {
        $html = '';
        
        //We presume that if EXTRACOLUMN_LINK then it means that we are in the ArtifactLink selector so we force read only mode
        if ($extracolumn === self::EXTRACOLUMN_LINK) {
            $read_only = true;
        }
        
        $current_user = UserManager::instance()->getCurrentUser();
        //Insert function aggregates
        if ($use_data_from_db) {
            $aggregate_functions_raw = array($this->getAggregatesDao()->searchByRendererId($this->getId()));
        } else {
            $aggregate_functions_raw = $this->getAggregates();
        }
        $aggregates = array();
        foreach ($aggregate_functions_raw as $rows) {
            if ($rows) {
                foreach ($rows as $row) {
                    //is the field used as a column?
                    if (isset($extracted_fields[$row['field_id']])) {
                        if (!isset($aggregates[$row['field_id']])) {
                            $aggregates[$row['field_id']] = array();
                        }
                        $aggregates[$row['field_id']][] = $row['aggregate'];
                    }
                }
            }
        }
        $queries = $this->buildOrderedQuery($matching_ids, $extracted_fields, $aggregates, '', false);
        $dao = new DataAccessObject();
        $results = array();
        foreach ($queries as $key => $sql) {
            if ($key === 'aggregates_group_by') {
                foreach ($sql as $k => $s) {
                    $results[$k] = $dao->retrieve($s);
                }
            } else {
                if ($dar = $dao->retrieve($sql)) {
                    $results = array_merge($results, $dar->getRow());
                }
            }
        }

        $html .= '<tr valign="top" class="tracker_report_table_aggregates">';
        if ($extracolumn) {
            $display_extracolumn = true;
            $classname = 'tracker_report_table_';
            if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                $classname .= 'masschange';
            } else if ($extracolumn === self::EXTRACOLUMN_LINK) {
                $classname .= 'link';
            } else if ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                $classname .= 'unlink';
            } else {
                $display_extracolumn = false;
            }

            if ($display_extracolumn) {
                $html .= '<td class="' . $classname . '" width="1">';
                $html .= '</td>';
            }
        }
        if (!$only_one_column) {
            $html .= '<td width="16"></td>';
        }
        $hp = Codendi_HTMLPurifier::instance();
        foreach ($columns as $column) {
            if ($column['field']->isUsed()) {
                $html .= '<td class="tracker_report_table_column_'. $column['field']->getId() .'">';
                if (!$read_only && $this->report->userCanUpdate(UserManager::instance()->getCurrentUser())) {
                    if ($column['field']->getAggregateFunctions()) {
                        $html .= '<div class="tracker_aggregate_function_add_panel">';
                        $html .= '<select name="tracker_aggregate_function_add[' . (int) $column['field']->getId() . ']">';
                        $html .= '<option value="" selected="selected">' . $GLOBALS['Language']->getText('plugin_tracker_aggregate', 'toggle') . '</option>';
                        foreach ($column['field']->getAggregateFunctions() as $f) {
                            $classname = 'tracker_aggregate_function_add_';
                            if (isset($aggregates[$column['field']->getId()]) && in_array($f, $aggregates[$column['field']->getId()])) {
                                $classname .= 'used';
                            } else {
                                $classname .= 'unused';
                            }
                            $html .= '<option value="' . $hp->purify($f, CODENDI_PURIFIER_CONVERT_HTML) . '" class="'. $classname .'">' . $GLOBALS['Language']->getText('plugin_tracker_aggregate', $f . '_sel') . '</option>';
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                    }
                }
                if (isset($aggregates[$column['field']->getId()])) {
                    $html .= '<ul class="tracker_function_aggregate_results">';
                    foreach ($aggregates[$column['field']->getId()] as $f) {
                        if (isset($results[$column['field']->getName() . '_' . $f])) {
                            $html .= '<li>';
                            $html .= '<strong>' . $GLOBALS['Language']->getText('plugin_tracker_aggregate', $f . '_title') . '</strong> ';
                            if (is_a($results[$column['field']->getName() . '_' . $f], 'DataAccessResult')) {
                                if ($row = $results[$column['field']->getName() . '_' . $f]->getRow()) {
                                    if (isset($row[$column['field']->getName() . '_' . $f])) {
                                        //this case is for multiple selectbox/count
                                        $html .= $hp->purify($this->formatAggregateResult($row[$column['field']->getName() . '_' . $f]), CODENDI_PURIFIER_CONVERT_HTML);
                                    } else {
                                        $html .= '<br />';
                                        $html .= '<table><tbody>';
                                        $i = 0;
                                        foreach ($results[$column['field']->getName() . '_' . $f] as $row) {
                                            $html .= '<tr class="'. html_get_alt_row_color(++$i) .'"><td style="font-weight:bold;">';
                                            if ($row['label'] === null) {
                                                $html .= '<em>'. $GLOBALS['Language']->getText('global', 'null') .'</em>';
                                            } else {
                                                $html .= $hp->purify($row['label'], CODENDI_PURIFIER_CONVERT_HTML);
                                            }
                                            $html .= '</td><td>';
                                            $html .= $hp->purify($this->formatAggregateResult($row['value']), CODENDI_PURIFIER_CONVERT_HTML);
                                            $html .= '</td></tr>';
                                        }
                                        $html .= '</tbody></table>';
                                    }
                                }
                            } else {
                                $html .= $hp->purify($this->formatAggregateResult($results[$column['field']->getName() . '_' . $f]), CODENDI_PURIFIER_CONVERT_HTML);
                            }
                            $html .= '</li>';
                        }
                    }
                    $html .= '</ul>';
                }
                $html .= '</td>';
            }
        }
        $html .= '</tr>';
        return $html;
    }
    
    protected function formatAggregateResult($value) {
        if (is_numeric($value)) {
            $decimals = 2;
            if (round($value) == $value) {
                $decimals = 0;
            }
            return round($value, $decimals);
        }
        return $value;
    }
    
    /**
     * Extract the fields from columns:
     * 
     * @param array $columns [ 0 => { 'field' => F1, 'width' => 40 }, 1 => { 'field' => F2, 'width' => 40 } ]
     *
     * @return array [ F1, F2 ]
     */
    public function extractFieldsFromColumns($columns) {
        $fields = array();
        $f = create_function('$v, $i, $t', '$t["fields"][$v["field"]->getId()] = $v["field"];');
        array_walk($columns, $f, array('fields' => &$fields));
        return $fields;
    }
    
    /**
     * Build oredered query
     *
     * @param array                       $matching_ids The artifact to display
     * @param Tracker_FormElement_Field[] $fields       The fields to display
     *
     * @return array of sql queries
     */
    protected function buildOrderedQuery($matching_ids, $fields, $aggregates = false, $store_in_session = true) {
        if ($aggregates) {
            $select = " SELECT 1 ";
        } else {
            $select = " SELECT a.id AS id, c.id AS changeset_id ";
        }

        $from   = " FROM tracker_artifact AS a INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id) ";
        $where  = " WHERE a.id IN (". $matching_ids['id'] .") 
                      AND c.id IN (". $matching_ids['last_changeset_id'] .") ";
        if ($aggregates) {
            $group_by = '';
            $ordering = false;
        } else {
            $group_by = ' GROUP BY id ';
            $ordering = true;
        }
        
        $additionnal_select = array();
        $additionnal_from   = array();
        
        foreach($fields as $field) {
            if ($field->isUsed()) {
                $sel = false;
                if ($aggregates) {
                    if (isset($aggregates[$field->getId()])) {
                        if ($a = $field->getQuerySelectAggregate($aggregates[$field->getId()])) {
                            $sel = $a['same_query'];
                            if ($sel) {
                                $additionnal_select[] = $sel;
                                $additionnal_from[] = $field->getQueryFromAggregate();
                            }
                        }
                    }
                } else {
                    $sel = $field->getQuerySelect();
                    if ($sel) {
                        $additionnal_select[] = $sel;
                        $additionnal_from[] = $field->getQueryFrom();
                    }
                }
            }
        }
        
        //build an array of queries (due to mysql max join limit
        $queries = array();
        $sys_server_join = intval($GLOBALS['sys_server_join']) - 3;
        if ($sys_server_join <= 0) { //make sure that the admin is not dumb
            $sys_server_join = 20; //default mysql 60 / 3 (max of 3 joins per field)
        }
        
        $additionnal_select_chunked = array_chunk($additionnal_select, $sys_server_join);
        $additionnal_from_chunked   = array_chunk($additionnal_from, $sys_server_join);
        
        //both arrays are not necessary the same size
        $n = max(count($additionnal_select_chunked), count($additionnal_from_chunked));
        for ($i = 0 ; $i < $n ; ++$i) {
            
            //init the select and the from...
            $inner_select = $select;
            $inner_from   = $from;
            
            //... and populate them
            if (isset($additionnal_select_chunked[$i]) && count($additionnal_select_chunked[$i])) {
                $inner_select .= ', '. implode(', ', $additionnal_select_chunked[$i]);
            }
            if (isset($additionnal_from_chunked[$i]) && count($additionnal_from_chunked[$i])) {
                $inner_from .= implode(' ', $additionnal_from_chunked[$i]);
            }
            
            //build the query
            $sql = $inner_select . $inner_from . $where . $group_by;
            
            //add it to the pool
            $queries[] = $sql;
        }
        
        //Add group by aggregates
        if ($aggregates) {
            foreach($fields as $field) {
                if ($field->isUsed()) {
                    if (isset($aggregates[$field->getId()])) {
                        if ($a = $field->getQuerySelectAggregate($aggregates[$field->getId()])) {
                            foreach($a['separate_queries'] as $sel) {
                                $queries['aggregates_group_by'][$field->getName() .'_'. $sel['function']] = "SELECT ". 
                                    $sel['select'] . 
                                    $from .' '. $field->getQueryFromAggregate() . 
                                    $where . 
                                    ($sel['group_by'] ? " GROUP BY ". $sel['group_by'] : '');
                            }
                        }
                    }
                }
            }
        }
        
        //only sort if we have 1 query
        // (too complicated to sort on multiple queries)
        if ($ordering && count($queries) === 1) {
            $sort = $this->getSort($store_in_session);
            if ($this->sortHasUsedField($store_in_session)) {
                $order = array();
                foreach($sort as $s) {
                    if ( !empty($s['field']) && $s['field']->isUsed()) {
                        $order[] = $s['field']->getQueryOrderby() .' '. ($s['is_desc'] ? 'DESC' : 'ASC');
                    }
                }
                $queries[0] .= " ORDER BY ". implode(', ', $order);
            }
        }
        if ( empty($queries) ) {
            $queries[] = $select.$from.$where.$group_by;
        }
        
        return $queries;
    }
    
    protected function _fetchMassChange($matching_ids, $total_rows, $offset) {
        $html    = '';
        $tracker = $this->report->getTracker();
        if ($tracker->userIsAdmin()) {
            $nb_art    = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
            $first_row = ($nb_art / $this->chunksz) + $offset;
            $last_row  = $first_row + $this->chunksz;
            $html .= '<form method="POST" action="" id="tracker_report_table_masschange_form">';
            $html .= '<input type="hidden" name="func" value="display-masschange-form" />';
            //build input for masschange all searched art ids
            foreach ( explode(',', $matching_ids['id']) as $id ) {
                $html .= '<input type="hidden" name="masschange_aids_all[]" value="'. $id .'"/>';
            }
            $html .= '<div id="tracker_report_table_masschange_panel">';
            $html .= '<input id="masschange_btn_checked" type="submit" name="renderer_table[masschange_checked]" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_report', 'mass_change_checked', $first_row, $last_row) .'" />';
            $html .= '<input id="masschange_btn_all" type="submit" name="renderer_table[masschange_all]" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_report', 'mass_change_all', $total_rows) .'" />';
            $html .= '</div>';
            $html .= '</form>';
        }
        return $html;
    }
    
    protected function getFieldFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
    /**
     * Duplicate the renderer
     */
    public function duplicate($from_renderer, $field_mapping) {
        //duplicate sort
        $this->getSortDao()->duplicate($from_renderer->id, $this->id, $field_mapping);
        //duplicate columns
        $this->getColumnsDao()->duplicate($from_renderer->id, $this->id, $field_mapping);
        //duplicate aggregates
        $this->getAggregatesDao()->duplicate($from_renderer->id, $this->id, $field_mapping);
    }
    
    public function getType() {
        return self::TABLE;
    }
    
    /**
     * Process the request
     * @param Request $request
     */
    public function processRequest(TrackerManager $tracker_manager, $request, $current_user) {
        $renderer_parameters = $request->get('renderer_table');
        $this->initiateSession();
        if ($renderer_parameters && is_array($renderer_parameters)) {
            //Update the chunksz parameter
            if (isset($renderer_parameters['chunksz'])) {
                $new_chunksz = abs((int)$renderer_parameters['chunksz']);
                if ($new_chunksz && ($this->chunksz != $new_chunksz)) {
                    $this->report_session->set("{$this->id}.chunksz", $new_chunksz);
                    $this->report_session->setHasChanged();
                    $this->chunksz = $new_chunksz;
                }
            }
            
            //Add an aggregate function
            if (isset($renderer_parameters['add_aggregate']) && is_array($renderer_parameters['add_aggregate'])) {
                list($field_id, $agg) = each($renderer_parameters['add_aggregate']);
                
                //Is the field used by the tracker?
                $ff = $this->getFieldFactory();
                if ($field = $ff->getUsedFormElementById($field_id)) {
                    //Has the field already an aggregate function?
                    $aggregates = $this->getAggregates();
                    if (isset($aggregates[$field_id])) {
                        //Yes. Check if it has already the wanted aggregate function
                        $found = false;
                        reset($aggregates[$field_id]);
                        while (!$found && (list($key,$row) = each($aggregates[$field_id]))) {
                            if ($row['aggregate'] === $agg) {
                                $found = true;
                                //remove it (toggle)
                                unset($aggregates[$field_id][$key]);
                                $this->report_session->set("{$this->id}.aggregates.{$field_id}", $aggregates[$field_id]);
                            }
                        }
                        if (!$found) {
                            //Add it
                            $aggregates[$field_id][] = array('renderer_id' => $this->id, 'field_id' => $field_id, 'aggregate' => $agg);
                            $this->report_session->set("{$this->id}.aggregates.{$field_id}", $aggregates[$field_id]);
                        }
                        $this->report_session->setHasChanged();
                        //TODO
                    } else {
                        //No. Add it
                        $this->report_session->set("{$this->id}.aggregates.{$field_id}", array(array('renderer_id' => $this->id, 'field_id' => $field_id, 'aggregate' => $agg)));
                        $this->report_session->setHasChanged();
                    }
                }
            }

            //toggle a sort column
            if (isset($renderer_parameters['sort_by'])) {
                $sort_by = (int)$renderer_parameters['sort_by'];
                if ($sort_by) {
                    
                    //Is the field used by the tracker?
                    $ff = $this->getFieldFactory();
                    if ($field = $ff->getUsedFormElementById($sort_by)) {
                        //Is the field used as a column?
                        $columns = $this->getColumns();
                        if (isset($columns[$sort_by])) {
                            //Is the field already used to sort results?
                            $sort_fields = $this->getSort();
                            if (isset($sort_fields[$sort_by])) {
                                $is_desc = &$this->report_session->get("{$this->id}.sort.{$sort_by}.is_desc");
                                //toggle
                                $desc = 1;
                                if ($is_desc == 1) {
                                    $desc = 0;
                                }
                                $this->report_session->set("{$this->id}.sort.{$sort_by}.is_desc", $desc);
                                $this->report_session->setHasChanged();
                            } else {
                                if (!$this->multisort) {
                                    //Drop existing sort
                                    foreach ($sort_fields as $id => $sort_field) {
                                        $this->report_session->remove("{$this->id}.sort", $id);
                                    }                                 
                                } 
                                //Add new sort                             
                                $this->report_session->set("{$this->id}.sort.{$sort_by}", array ('is_desc' => 0, 'rank' => count($this->report_session->get("{$this->id}.sort")) ));
                                $this->report_session->setHasChanged();
                            }
                        }
                    }
                }
            }
            
            //Reset sort
            if (isset($renderer_parameters['resetsort'])) {
                //Drop existing sort
                $this->report_session->remove("{$this->id}","sort");
                $this->report_session->setHasChanged();
            }
            
            //Toggle multisort
            if (isset($renderer_parameters['multisort'])) {
                $sort_fields = $this->getSort();
                list($keep_it,) = each($sort_fields);
                $this->multisort = !$this->multisort;
                $this->report_session->set("{$this->id}.multisort", $this->multisort);
                if (!$this->multisort) {
                    $sort = $this->report_session->get("{$this->id}.sort");
                    foreach($sort as $field_id => $properties) {
                        if ($field_id != $keep_it) {
                            $this->report_session->remove("{$this->id}.sort", $field_id);
                            $this->report_session->setHasChanged();
                        }
                    }
                }
            }
            
            //Remove column
            if (isset($renderer_parameters['remove-column'])) {
                if ($field_id = (int)$renderer_parameters['remove-column']) {
                    //Is the field used by the tracker?
                    $ff = $this->getFieldFactory();
                    if ($field = $ff->getUsedFormElementById($field_id)) {
                        //Is the field used as a column?
                        $columns = $this->getColumns();
                        if (isset($columns[$field_id])) {
                            //Is the field already used to sort results?
                            $sort_fields = $this->getSort();
                            if (isset($sort_fields[$field_id])) {
                                //remove from session
                                $this->report_session->remove("{$this->id}.sort", $field_id);
                                $this->report_session->setHasChanged();
                            }
                            //remove from session
                            $this->report_session->remove("{$this->id}.columns", $field_id);
                            $this->report_session->setHasChanged();
                        }
                    }
                }
            }
            
            //Add column
            if (isset($renderer_parameters['add-column'])) {
                if ($field_id = (int)$renderer_parameters['add-column']) {
                    $added = false;
                    //Is the field used by the tracker?
                    $ff = $this->getFieldFactory();
                    if ($field = $ff->getUsedFormElementById($field_id)) {
                        //Is the field used as a column?
                        $columns = $this->getColumns();
                        if (!isset($columns[$field_id])) {
                            $session_table_columns = $this->report_session->get("{$this->id}.columns");
                            $nb_col = count( $session_table_columns );
                            //Update session with new column
                            $this->report_session->set("{$this->id}.columns.{$field_id}", array('width' => 12, 'rank' => $nb_col) );
                            $this->report_session->setHasChanged();
                            $added = true;
                        }
                    }
                    if ($added && $request->isAjax()) {
                        $matching_ids    = $this->report->getMatchingIds();
                        $offset          = (int)$request->get('offset');
                        $extracolumn     = self::NO_EXTRACOLUMN;
                        $total_rows      = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
                        
                        echo $this->_fetchHead($extracolumn, $field_id);
                        echo $this->_fetchBody($matching_ids, $total_rows, $offset, $extracolumn, $field_id);
                    }
                }
            }
            
            //Reorder columns
            if (isset($renderer_parameters['reorder-column']) && is_array($renderer_parameters['reorder-column'])) {
                list($field_id,$new_position) = each($renderer_parameters['reorder-column']);
                $field_id     = (int)$field_id;
                $field_id_s = $field_id;
                $new_position = (int)$new_position;
                if ($field_id) {
                    //Is the field used by the tracker?
                    $ff = $this->getFieldFactory();
                    if ($field = $ff->getUsedFormElementById($field_id)) {
                        //Is the field used as a column?
                        $columns = $this->getColumns();
                        if (isset($columns[$field_id])) {
                            $columns = &$this->report_session->get("{$this->id}.columns");
                            if ($new_position == '-1') {
                                //beginning
                                foreach ($columns as $id => $properties) {
                                    $columns[$id]['rank'] = $properties['rank'] + 1;
                                }
                                $columns[$field_id]['rank'] = 0;
                            } else if ($new_position == '-2') {
                                //end
                                $max = 0;
                                foreach ($columns as $id => $properties) {
                                    if ($properties['rank'] > $max) {
                                        $max = $properties['rank'];
                                    }
                                }
                                $columns[$field_id]['rank'] = $max + 1;
                            } else {
                                //other case
                                $replaced_rank = $columns[$new_position]['rank'] + 1;   // rank of the element to shift right
                                foreach ($columns as $id => $properties) {
                                    if ($properties['rank'] >= $replaced_rank && $id != $field_id) {
                                       $columns[$id]['rank'] += 1;
                                    }
                                }                                    
                                $columns[$field_id]['rank'] = $replaced_rank;
                            }
                            $this->report_session->setHasChanged();
                        }
                    }
                }
            }
            
            //Resize column
            if (isset($renderer_parameters['resize-column']) && is_array($renderer_parameters['resize-column'])) {
                $ff = $this->getFieldFactory();
                foreach ($renderer_parameters['resize-column'] as $field_id => $new_width) {
                    $field_id  = (int)$field_id;
                    $new_width = (int)$new_width;
                    if ($field_id) {
                        //Is the field used by the tracker?
                        if ($field = $ff->getUsedFormElementById($field_id)) {
                            //Is the field used as a column?
                            $columns = $this->getColumns();
                            if (isset($columns[$field_id])) {
                                $old_width = $columns[$field_id]['width'];
                                $this->report_session->set("{$this->id}.columns.{$field_id}.width", $new_width);
                                $this->report_session->setHasChanged();
                            }
                        }
                    }
                }
            }
            
            //export
            if (isset($renderer_parameters['export'])) {
                $only_columns = isset($renderer_parameters['export_only_displayed_fields']) && $renderer_parameters['export_only_displayed_fields'];
                $this->exportToCSV($only_columns);
            }
        }
    }
    
    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping) {
        parent::exportToXML($root, $xmlMapping);
        $root->addAttribute('chunksz', $this->chunksz);
        if ($this->multisort) { 
            $root->addAttribute('multisort', $this->multisort);
        }
        $child = $root->addChild('columns');
        foreach ($this->getColumns() as $key => $col) {
            $child->addChild('field')->addAttribute('REF', array_search($key, $xmlMapping));
        }
        //TODO : add aggregates in XML export
        /*if ($this->getAggregates()) {
            $child = $root->addChild('aggregates');
            foreach ($this->getAggregates() as $field_id => $aggregates) {
                foreach ($aggregates as $aggregate) {
                    $child->addChild('aggregate')->addAttribute('REF', array_search($field_id, $xmlMapping))
                                                 ->addAttribute('function', $aggregate);
                }
            }
        }*/
        if ($this->getSort()) {
            $child = $root->addChild('sort');
            foreach ($this->getSort() as $key => $sort) {
                 $child->addChild('field')->addAttribute('REF', array_search($key, $xmlMapping));
            }
        }
    }
    
    /**
     * Export results to csv
     *
     * @param bool $only_columns True if we need to export only the displayed columns. False for all the fields.
     *
     * @return void
     */
    protected function exportToCSV($only_columns) {
        $matching_ids = $this->report->getMatchingIds();
        $total_rows   = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        
        if ($only_columns) {
            $fields = $this->extractFieldsFromColumns($this->reorderColumnsByRank($this->getColumns()));
        } else {
            $fields = Tracker_FormElementFactory::instance()->getUsedFields($this->report->getTracker());
        }
        
        $lines = array();
        $head  = array('aid');
        foreach ($fields as $field) {
            if ($field->isUsed() && $field->userCanRead() && ! is_a($field, 'Tracker_FormElement_Field_ArtifactId')) {
                $head[] = $field->getName();
            }
        }
        $lines[] = $head;
        
        $queries = $this->buildOrderedQuery($matching_ids, $fields);
        $dao = new DataAccessObject();
        $results = array();
        foreach($queries as $sql) {
            $results[] = $dao->retrieve($sql);
        }
        
        
        if (!empty($results[0])) {
            $i = 0;
            //extract the first results
            $first_result = array_shift($results);
            
            //loop through it
            foreach ($first_result as $row) { //id, f1, f2
                
                //merge the row with the other results
                foreach ($results as $result) {
                    //[id, f1, f2] + [id, f3, f4]
                    $row = array_merge($row, $result->getRow());
                    //row == id, f1, f2, f3, f4...
                }
                
                //build the csv line
                $line = array();
                $line[] = $row['id'];
                foreach($fields as $field) {
                    if($field->isUsed() && $field->userCanRead() && ! is_a($field, 'Tracker_FormElement_Field_ArtifactId')) {
                        $line[] = $field->fetchCSVChangesetValue($row['id'], $row['changeset_id'], $row[$field->name]);
                    }
                }
                $lines[] = $line;
            }
            
            $separator = ",";   // by default, comma.
            $user = UserManager::instance()->getCurrentUser();
            $separator_csv_export_pref = $user->getPreference('user_csv_separator');
            switch ($separator_csv_export_pref) {
            case "comma":
                $separator = ',';
                break;
            case "semicolon":
                $separator = ';';
                break;
            case "tab":
                $separator = chr(9);
                break;
            }
            
            $http = Codendi_HTTPPurifier::instance();
            $file_name = str_replace(' ', '_', 'artifact_' . $this->report->getTracker()->getItemName());
            header('Content-Disposition: filename='. $http->purify($file_name) .'_'. $this->report->getTracker()->getProject()->getUnixName(). '.csv');
            header('Content-type: text/csv');
            foreach($lines as $line) {
                fputcsv(fopen("php://output", "a"), $line, $separator, '"');
            }
            die();
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Unable to export (too many fields?)');
        }
    }
    
    /**
     * Save columns in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveColumnsRenderer($renderer_id) {
        $columns = $this->getColumns();
        $ff = $this->getFieldFactory();
        //Add columns in db
        if (is_array($columns)) {
             foreach($columns as $field_id => $properties) {
                 if ($field = $ff->getUsedFormElementById($field_id)) {
                     $this->getColumnsDao()->create($renderer_id, $field_id, $properties['width'], $properties['rank']);
                 }
             }
        }
    }
    
    /**
     * Save aggregates in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveAggregatesRenderer($renderer_id) {
        $aggregates = $this->getAggregates();
        $ff = $this->getFieldFactory();
        //Add columns in db
        if (is_array($aggregates)) {
            $dao = $this->getAggregatesDao();
            foreach($aggregates as $field_id => $aggs) {
                if ($field = $ff->getUsedFormElementById($field_id)) {
                    foreach ($aggs as $agg) {
                        $dao->create($renderer_id, $field_id, $agg['aggregate']);
                    }
                }
            }
        }
    }
    
    /**
     * Save multisort/chunksz in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveRendererProperties ($renderer_id) {
        $dao = new Tracker_Report_Renderer_TableDao();
        if (!$dao->searchByRendererId($renderer_id)->getRow()) {
            $dao->create($renderer_id, $this->chunksz);
        }
        $dao->save($renderer_id, $this->chunksz, $this->multisort);
    }
    
    /**
     * Save sort in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveSortRenderer($renderer_id) {
        $sort = $this->getSort();
        if (is_array($sort)) {
            foreach ($sort as $field_id => $properties) {
                $this->getSortDao()->create($renderer_id, $field_id, $properties['is_desc'], $properties['rank']);
            }
        }
    }
    
    /**
     * Create a renderer - add in db
     *     
     * @return bool true if success, false if failure
     */
    public function create() {
        $success = true;
        $rrf = Tracker_Report_RendererFactory::instance();

        if ($renderer_id = $rrf->saveRenderer($this->report, $this->name, $this->description, $this->getType())) {
            //columns
            $this->saveColumnsRenderer($renderer_id);
            
            //aggregates
            $this->saveAggregatesRenderer($renderer_id);
            
            //MultiSort/Chunksz
            $this->saveRendererProperties($renderer_id);
                        
            //Sort
            $this->saveSortRenderer($renderer_id);
        }
        return $success;
    }
    
    
    /**
     * Update the renderer
     *     
     * @return bool true if success, false if failure
     */
    public function update() {
        $success = true;
        if ($this->id > 0) {
            //first delete existing columns and sort
            $this->getSortDao()->delete($this->id);
            $this->getColumnsDao()->delete($this->id);
            $this->getAggregatesDao()->deleteByRendererId($this->id);
            
            //columns
            $this->saveColumnsRenderer($this->id);
            
            //aggregates
            $this->saveAggregatesRenderer($this->id);
            
            //MultiSort/Chunksz
            $this->saveRendererProperties($this->id);
            
            //Sort
            $this->saveSortRenderer($this->id);
            
        }
        return $success;
    }
    
    /**
     * Set the session
     *
     */
    public function setSession($renderer_id = null) {
        if(!$renderer_id) {
            $renderer_id = $this->id;
        }
        $this->report_session->set("{$this->id}.name", $this->name);
        $this->report_session->set("{$this->id}.description", $this->description);
        $this->report_session->set("{$this->id}.chunksz", $this->chunksz); 
        $this->report_session->set("{$this->id}.multisort", $this->multisort);
        $this->report_session->set("{$this->id}.rank", $this->rank);
    }

    /**
     * Finnish saving renderer to database by creating colunms
     * 
     * @param Report_Renderer $renderer containing the columns 
     */
    public function afterSaveObject($renderer) {
        $this->saveColumns($renderer->getColumns());
        $this->saveAggregates($renderer->getAggregates());
        $this->saveSort($renderer->getSort());
    }
    
    /**
     *Test if sort contains at least one used field
     *
     * @return bool, true f sort has at least one used field
     */
    public function sortHasUsedField($store_in_session = true) {
        $sort = $this->getSort($store_in_session);
        foreach($sort as $s) {
            if ($s['field']->isUsed()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     *Test if multisort does not contain unused fields
     *
     *@return bool true if still multisort
     */
    public function isMultisort(){
        $sort = $this->getSort();
        $used = 0;
        foreach($sort as $s) {
            if ($s['field']->isUsed()) {
                $used ++;
            }
        }
        if($used < 2) {
            return false;
        } else {
            return true;
        }
    }
}

?>
