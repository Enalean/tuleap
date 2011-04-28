<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

class BuildMenuVisitor {
    var $level;

    function VisitorBuildMenu() {
        $this->level = 0;
    }

    function getHtml() {
        return $this->output;
    }

    function createLi(&$treeNode) {
        $html = '';
        if($treeNode->data['link']) {
            $selected = '';
            if(array_key_exists('selected',  $treeNode->data) && $treeNode->data['selected'] === true) {
                $selected = ' class="current"';
            }
            $_addr = '<a href="'.$treeNode->data['link'].'"'.$selected.'>'.$treeNode->data['title'].'</a>';
            
            $html = "<li>".$_addr."</li>\n";
        }
        return $html;
    }

    function visitLevel($nodeArray, $level) {                   
        $html = '';
        do {
            $childrenArray = array();
            $level++;
            $html .= '<div id="level_'.$level.'">'."\n";
            $html .= '<ul>'."\n";
            foreach($nodeArray as $node) {
                if($node->hasChildren()) {
                    $childrenArray = array_merge($childrenArray, $node->getChildren());
                }
                $html .= $this->createLi($node);
            }
            $html .= "</ul>\n";
            $html .= "</div>\n";
            $nodeArray = $childrenArray;
        } while(count($nodeArray) > 0);

        return $html;
    }

    function visit(&$treeNode) {        
        $this->output = $this->visitLevel($treeNode->getChildren(), 0);
    }
}
?>
