<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
 */

/**
 * Define an html field
 */
abstract class HTML_Element {

    protected $params;
    protected $name;
    protected $value;
    protected $label;
    protected $desc;
    protected $id;
    protected static $last_id = 0;
    
    public function __construct($label, $name, $value, $desc='') {
        $this->name   = $name;
        $this->value  = $value;
        $this->label  = $label;
        $this->id     = 'customfield_'. self::$last_id++;
        $this->desc  = $desc;
        $this->params = array();
    }
    public function getValue() {
        return $this->value;
    }
    protected function renderLabel() {
        $hp = Codendi_HTMLPurifier::instance();
        return '<label for="'. $this->id .'">'.  $hp->purify($this->label, CODEX_PURIFIER_CONVERT_HTML)  .'</label>';
    }
    public function render() {
        $html  = '';
        $html .= $this->renderLabel();
        $html .= '<br />';
        if(trim($this->desc)!=''){
            $html .=$this->desc.'<br/>';
        }
        $html .= $this->renderValue();;
        return $html;
    }
    protected function renderValue() {
        $hp = Codendi_HTMLPurifier::instance();
        return  $hp->purify($this->value, CODEX_PURIFIER_CONVERT_HTML) ;
    }
    public function getId() {
        return $this->id;
    }
}
?>
