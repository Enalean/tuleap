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
 *
 * 
 */

class RSS {
    var $channel;
    var $items;
    function RSS($channel) {
        $this->channel = $channel;
        $this->items   = array();
    }
    
    function addItem($item) {
        $this->items[] = $item;
    }
    
    function display() {
        $channel_elements   = array('title', 'description', 'link', 'language', 'rating', 'image', 'textinput', 'copyright', 'pubDate', 'lastBuildDate', 'docs', 'managingEditor', 'webMaster', 'skipHours', 'skipDays');
        $image_elements     = array('title', 'description', 'link', 'url', 'width', 'height');
        $textinput_elements = array('title', 'description', 'link', 'name');
        $item_elements      = array('title', 'description', 'link', 'dc:creator', 'pubDate', 'guid');
        header("Content-Type: text/xml");
        echo '<?xml version="1.0"  encoding="UTF-8" ?>'. "\n";
        echo '<?xml-stylesheet type="text/xsl"  href="/export/rss.xsl" ?>'. "\n";
        echo '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">';
        echo '<channel>';
        foreach($channel_elements as $channel_element) {
            if (isset($this->channel[$channel_element])) {
                echo '<'. $channel_element .'>';
                $special = $channel_element.'_elements';
                if (isset($$special)) {
                    foreach($$special as $element) {
                        if (isset($this->channel[$channel_element][$element])) {
                            echo '<'. $element .'>';
                            echo $this->channel[$channel_element][$element];
                            echo '</'. $element .'>';
                        }
                    }
                } else {
                    echo $this->channel[$channel_element];
                }
                echo '</'. $channel_element .'>';
            }
        }
        foreach($this->items as $item) {
            echo '<item>';
            foreach($item_elements as $item_element) {
                if (isset($item[$item_element])) {
                    echo '<'. $item_element .'>';
                    echo $item[$item_element];
                    echo '</'. $item_element .'>';
                }
            }
            echo '</item>';
        }
        echo '</channel>';
        echo '</rss>';
    }
}
?>
