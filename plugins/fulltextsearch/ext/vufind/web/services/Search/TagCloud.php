<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

require_once 'services/MyResearch/lib/Tags.php';
require_once 'services/MyResearch/lib/Resource_tags.php';

global $configArray;

class TagCloud extends Action 
{
    function TagCloud()
    {
    }
    
    function launch()
    {
        global $interface;
        global $configArray;
        
        $data = getTagCloud();
        $interface->assign("tagCloud", $data);
        $interface->setTemplate("tagcloud-home.tpl");
        $interface->display("layout.tpl");
    }
}

function getTagCloud()
{
    //global $interface;
    global $configArray;
    
    
    $tags = new Tags();
    
    // Specify different font sizes in descending order here
    $fontSizes = array(5, 4, 3, 2, 1);
    $nFonts = count($fontSizes); 
    
    // no of different tags to display
    $RecLimit = 50;
    
    // Query to retrive tags and their
    // count
    $query = "SELECT tags.tag, COUNT(tag) as cnt " .
             "FROM tags, resource_tags" . " " .
             "WHERE tags.id = resource_tags.tag_id " .
             "GROUP BY tags.tag " .
             "ORDER BY cnt DESC, tag " .
             "LIMIT $RecLimit "
             ;
    
    $tags->query($query);
    $actualRecs = $tags->N;
    
    // Create data array as 
    // key = tag_name and value = tag_count
    //from the results returned by query
    $data = array();
    if($actualRecs) {
        while($tags->fetch()) {
            $data["$tags->tag"] = $tags->cnt;
        }
    } else {
        return;
    }
    $temp = $data;
    // sort array in alphabetical
    // order of its keys
    uksort($data, "strnatcasecmp");
    
    // Create arry which contains only
    // count of all tags
    $onlyCnt = array();
    foreach($temp as $item) {
            $onlyCnt[] = $item;
    }
    // create array which will contain only
    // uniqe tag counts
    $DistinctValues = array($onlyCnt[0]);
    for($i = 1; $i < count($onlyCnt); $i++) {
        if($onlyCnt[$i] != $onlyCnt[$i - 1]) {
            $DistinctValues[] = $onlyCnt[$i];
        }
    }
    
    $cntDistinct = count($DistinctValues);
    
    // set step which will
    // decide when to change font size
    $step = 1;
    $mod = 0;
    if($cntDistinct > $nFonts) {
        $step = (int)($cntDistinct / $nFonts);
        $mod = $cntDistinct % $nFonts;
    }
    
    $distinctToFont = array();
    $fontIndex = 0;
    $stepCnt = 0;
    for($i = 0; $i < $cntDistinct; $i++) {
        $distinctToFont["{$DistinctValues[$i]}"] = $fontSizes[$fontIndex];
        $stepCnt++;
        if($mod && (($nFonts - ($fontIndex + 1)) == $mod)) {
            $step++;
            $fontIndex++;
            $stepCnt = 0;
        }
        if($stepCnt == $step) {
            $fontIndex++;
            $stepCnt = 0;
        }
    }
    
    foreach($data as $key => $value) {
        $data[$key] = $distinctToFont["$value"];
    }
    
    return $data;
}

?>
