<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once "Widget.class.php";

class StaticWidget extends Widget {
    protected $title   = "";
    protected $content = "";
    protected $rss     = "";
    
    public function __construct($title) {
        $this->title = $title;
    }
    
    public function display() {
        $GLOBALS['HTML']->widget($this, null, true, null, false, false, null, null);
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setContent($content) {
        $this->content = $content;
    }
    
    public function getContent() {
        return $this->content;
    }
    
    public function setRss($rss) {
        $this->rss = $rss;
    }
    
    public function getRssUrl($a, $b) {
        return $this->rss;
    }
    public function hasRss() {
        return ($this->rss !== "");
    }
}
?>