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

/* abstract */class Docman_View_ItemDetailsSection {
    
    var $id;
    var $title;
    var $item;
    var $url;
    var $hp;

    function __construct(&$item, $url, $id, $title) {
        $this->id     =  $id;
        $this->title  =  $title;
        $this->item   =& $item;
        $this->url    =  $url;
        $this->hp     =& Codendi_HTMLPurifier::instance();
    }
    
    function getId() {
        return $this->id;
    }
    function getTitle() {
        return $this->title;
    }
    function getContent() {
        return '';
    }
}

?>
