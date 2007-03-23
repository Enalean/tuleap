<?php

/**
* RSS
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
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
        $item_elements      = array('title', 'description', 'link');
        header("Content-Type: text/xml");
        echo '<?xml version="1.0"  encoding="ISO-8859-1" ?>'. "\n";
        echo '<?xml-stylesheet type="text/xsl"  href="/export/rss.xsl" ?>'. "\n";
        echo '<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">'. "\n";
        echo '<rss version="0.91">';
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
