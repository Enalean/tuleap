<?php
/*
 * Copyright� STMicroelectronics, 2006
 * Originally written by Manuel VACELET, STMicroelectronics, 2006
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
 *
 */
require_once('Docman_View_Browse.class.php');

class Docman_View_Table extends Docman_View_Browse {
    
    /* protected */ function _filter($params) {

        // No filter in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $reportArg = '';
        if(isset($params['report']) && $params['report'] !== false) {
            $reportArg = '<input type="hidden" name="report" value="'.$params['report'].'" />';
        }

        $action = isset($params['action']) ? $params['action'] : 'show';

        echo '
<!-- Filters -->
<fieldset class="docman_filters">
<legend>'. $GLOBALS['Language']->getText('plugin_docman', 'filters') .'</legend>
<form name="plugin_docman_filters" method="GET" action="?">
<input type="hidden" name="group_id" value="'.$params['group_id'].'" />
<input type="hidden" name="id" value="'.$params['item']->getId().'" />
<input type="hidden" name="action" value="'.$action.'" />
';
        echo $reportArg;


        if($params['filter'] !== null) {
            $fi =& $params['filter']->getFilterIterator();
            $fi->rewind();
            while($fi->valid()) {        
                $f =& $fi->current();
             
                $htmlFilter =& Docman_HtmlFilterFactory::getFromFilter($f);
                if($htmlFilter !== null) {
                    print $htmlFilter->toHtml('plugin_docman_filters');
                }
                
                $fi->next();
            }
        }              
echo '<input name="submit" type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_browse') .'">
</form>
</fieldset>
';
    }
    var $dfltParams = null;
    function _getDefaultUrlParams($params) {
        if($this->dfltParams === null) {
            $urlAction = 'show';
            if(isset($params['action'])) {
                if($params['action'] == 'search') {
                    $urlAction = $params['action'];
                }
            }
            $this->dfltParams = array('action' => $urlAction,
                                      'id'     => $params['item']->getId());

            $this->_initSearchAndSortParams($params);
        }
        return array_merge($this->dfltParams, $this->dfltSearchParams, $this->dfltSortParams);
    }

    function _buildSearchUrl($params, $extraParams = array()) {
        $parameters = array_merge($this->_getDefaultUrlParams($params), $extraParams);
        return $this->buildActionUrl($params, $parameters);
    }

    /* protected */ function _content($params) {      
        $itemBo = new Docman_ItemBo($params['group_id']);

        $itemIterator =& $itemBo->getItemList($params['item']->getId(), 
                                              array('user' => $params['user'],
                                                    'ignore_collapse' => true,
                                                    'filter' => $params['filter']));             

        $itemFactory =& new Docman_ItemFactory();


        $action = isset($params['action']) ? $params['action'] : 'show';
        $currentViewUrlParams = array('action' => $action,
                                      'id' => $params['item']->getId());

        // Limit browsing
        $offset = 25;
        $_low_limit  = 0;
        if(isset($params['start'])) {
            $_low_limit  = $params['start'];
        }
        $_high_limit = $_low_limit + $offset;
        $itemCounter = 0;

        $sortableColumns = array();
        /// {{{ Prepare "On column title click" sort
        if($params['filter'] !== null) {
            $fi = $params['filter']->getFilterIterator();
            $fi->rewind();
            while($fi->valid()) {        
                $f =& $fi->current();
             
                $sort = $f->getSort();
                if($sort == 1) {
                    $toggleValue = '0';
                    $toogleIcon = '<img src="'.util_get_image_theme("up_arrow.png").'" border="0" >';
                }
                else {
                    $toggleValue = '1';
                    $toogleIcon = '<img src="'.util_get_image_theme("dn_arrow.png").'" border="0" >';
                }

                //
                // URL
                //
                $toggleParam = array();
                $sortParam = $f->getSortParam();
                if($sortParam !== null) {
                    $toggleParam[$sortParam] = $toggleValue;
                }
                // Cannot use _buildSearchUrl because we don't want to keep previous sort_*.
                $this->_getDefaultUrlParams($params);
                $url = $this->buildActionUrl($params, array_merge($this->dfltParams, 
                                                                  $this->dfltSearchParams, 
                                                                  $toggleParam));
                
                $title = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_toggletitle');

                $link = $f->md->getName();
                if($sort !== null) {
                    $link .= '&nbsp;'.$toogleIcon;
                }

                $href = '<a href="'.$url.'" title="'.$title.'">'.$link.'</a>';

                $sortableColumns[$f->md->getLabel()] = $href;
                
                $fi->next();
            }
        }

        $settingsBo =  Docman_SettingsBo::instance($params['group_id']);
        $useStatus = $settingsBo->getMetadataUsage('status');

        if($useStatus) {
            $columnsOnReport = array('status', 'title', 'description', 'location', 'owner', 'update_date');
        }
        else {
            $columnsOnReport = array('title', 'description', 'location', 'owner', 'update_date');
        }

        $notSortableColumns['location'] = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_location');
        

        $columnsTitles = array();
        foreach($columnsOnReport as $column) {
            if(isset($sortableColumns[$column])) {
                $columnsTitles[] = $sortableColumns[$column];
            }
            else {
                $columnsTitles[] = $notSortableColumns[$column];
            }
        }

        $table =  html_build_list_table_top($columnsTitles);
        
        $altRowClass = 0;
        $itemIterator->rewind();
        while($itemIterator->valid()) {            
            $item =& $itemIterator->current();
            $type = $itemFactory->getItemTypeForItem($item);
            if($type != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                if($itemCounter >= $_low_limit && $itemCounter < $_high_limit) {                    
                    $trclass = html_get_alt_row_color($altRowClass++);
                    $table .=  "<tr class=\"".$trclass."\">\n";
            
                    // Status
                    if($useStatus) {
                        $table .= "<td>";
                        $eIter = $item->getHardCodedMetadataValue('status');
                        $love  = $eIter->current();
                        if($love->getId() > 0) {
                            $table .= $love->getName();
                        }
                        $table .= "</td>\n";
                    }

                    // Title
                    $docmanIcons =& $this->_getDocmanIcons($params);
                    $icon_src = $docmanIcons->getIconForItem($item, $params);
                    $icon = '<img src="'. $icon_src .'" class="docman_item_icon" />';
                    $table .=  "<td>";
                    $table .= '<span style="white-space: nowrap;">';
                    $table .= $icon;
                    $url = $this->buildActionUrl($params, 
                                                 array('action' => 'show',
                                                       'id' => $item->getId()), 
                                                 false,
                                                 true);
                    $table .= '<a href="'.$url.'">';
                    $table .=  htmlentities($item->getTitle(), ENT_QUOTES);
                    $table .=  '</a>';
                    $table .= $this->getItemMenu($item, $params);
                    $table .= '</span>';
                    $table .=  "</td>\n";

                    // Description
                    $table .=  "<td>";
                    $table .=  $item->getDescription();
                    $table .=  "</td>\n";

                    // Location
                    $table .=  "<td>";
                    $pathTitle =& $item->getPathTitle();
                    $pathId    =& $item->getPathId();
                    $pathUrl   = array();
                    foreach($pathTitle as $key => $title) {
                        if($key != 0) {
                            $id  = $pathId[$key];
                            $dfltParams = $this->_getDefaultUrlParams($params);
                            $dfltParams['id'] = $id;
                            $url = $this->buildActionUrl($params, $dfltParams);
                            $href = '<a href="'.$url.'">'.$title.'</a>';
                            $pathUrl[] = $href;
                        }
                    }
                    $table .= implode(' / ', $pathUrl);
                    $table .=  "</td>\n";		 

                    // Owner
                    $table .=  "<td>";
                    $table .=  user_getname($item->getOwnerId());
                    $table .=  "</td>\n";          
            
                    // Last Update
                    $table .=  "<td>";
                    $table .=  format_date("Y-m-j", $item->getUpdateDate());
                    $table .=  "</td>\n";

                    $table .=  "</tr>\n";
                }
                $itemCounter++;
            }
            $itemIterator->next();            
        }
        $table .= "</table>\n";        

        // Prepare Navigation Bar
        if($_low_limit > 0) {
            $firstUrl    = $this->_buildSearchUrl($params, array('start' => '0'));
            $first       = '<a href="'.$firstUrl.'">&lt;&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_begin').'</a>';
        }
        else {
            $first       = '&lt;&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_begin');
        }
   
        $previousOffset = $_low_limit - $offset;
        if($_low_limit > 0) {
            if($previousOffset < 0) {
                $previousOffset = 0;
            }
            $previousUrl = $this->_buildSearchUrl($params, array('start' => $previousOffset));
            $previous    = '<a href="'.$previousUrl.'">&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_previous').' '.$offset.'</a>';
        }
        else {
            $previous    = '&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_previous').' '.$offset;
        }

        if($_high_limit < $itemCounter) {
            $nextUrl     = $this->_buildSearchUrl($params, array('start' => $_high_limit));
            $next        = '<a href="'.$nextUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_next').' '.$offset.' &gt;</a>';
        }
        else {
            $next        = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_next').' '.$offset.' &gt;';
        }

        if($_high_limit < $itemCounter) {
            $lastOffset  = $itemCounter - $offset;
            $lastUrl     = $this->_buildSearchUrl($params, array('start' => $lastOffset));
            $last        = '<a href="'.$lastUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_end').' &gt;&gt;</a>';
        }
        else {
            $last        = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_end').' &gt;&gt';
        }

        $navbar = '<table border="0" width="100%"><tr><td width="50%" align="left">'.$first.' '.$previous.'</td><td width="50%" align="right">'.$next.' '.$last.'</td></tr></table>';

        print $navbar.$table;        
    }
}
?>
