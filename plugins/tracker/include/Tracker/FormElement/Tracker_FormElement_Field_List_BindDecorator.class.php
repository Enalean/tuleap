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

class Tracker_FormElement_Field_List_BindDecorator {
    public $field_id;
    public $value_id;
    public $r;
    public $g;
    public $b;
    public function __construct($field_id, $value_id, $r, $g, $b) {
        $this->field_id = $field_id;
        $this->value_id = $value_id;
        $this->r        = $r;
        $this->g        = $g;
        $this->b        = $b;
    }
    
    /**
     * Decorate a value.
     * @param string $value The value to decorate Don't forget to html-purify. 
     * @param boolean $full false if you want only the decoration
     * @return string html
     */
    public function decorate($value, $full = true) {
        $html = '';
        if ($full) {
            $html .= '<span style="white-space:nowrap;">';
        }
        $html .= self::fetchSquareColor('', $value, '', $this->r, $this->g, $this->b);
        if ($full) {
            $html .= ' '. $value .'</span>';
        }
        return $html;
    }
    
    public function decorateSelectOption() {
        return 'border-left: 16px solid '. $this->toHexa($this->r, $this->g, $this->b) .';';
    }
    
    /**
     * Display the color and allow the user to edit it
     * @param string $value The value to decorate Don't forget to html-purify. 
     * @param boolean $full false if you want only the decoration
     * @return string html
     */
    public function decorateEdit() {
        $html = '';
        $hexa = $this->toHexa($this->r, $this->g, $this->b);
        $id   = 'decorator_'. $this->field_id .'_'. $this->value_id;
        $html .= self::fetchSquareColor($id, $hexa, 'colorpicker', $this->r, $this->g, $this->b);
        $html .= '<input id="'. $id .'_field" type="text" size="6" autocomplete="off" name="bind[decorator]['. $this->value_id .']" value="'. $hexa .'" />';
        return $html;
    }
    
    /**
     * Display the transparent color and allow the user to edit it
     * @param string $value The value to decorate Don't forget to html-purify. 
     * @param boolean $full false if you want only the decoration
     * @return string html
     */
    public static function noDecoratorEdit($field_id, $value_id) {
        $html = '';
        $id   = 'decorator_'. $field_id .'_'. $value_id;
        $html .= self::fetchSquareColor($id, 'transparent', 'colorpicker', null, null, null, 'ic/layer-transparent.png');
        $html .= '<input id="'. $id .'_field" type="text" size="6" autocomplete="off" name="bind[decorator]['. $value_id .']" value="" />';
        return $html;
    }
    
    protected static function fetchSquareColor($id, $title, $classname, $r, $g, $b, $img = 'blank16x16.png') {
        $html = '';
        $bgcolor = '';
        if ($r !== null && $g !== null && $b !== null ) {
            $bgcolor .= "background-color:rgb($r, $g, $b);";
        }
        $html .= $GLOBALS['HTML']->getImage($img, array(
            'id'     => $id,
            'width'  => '16px',
            'height' => '16px',
            'style'  => 'vertical-align:middle; '. $bgcolor,
            'title'  => $title,
            'class'  => $classname,
        ));
        return $html;
    }
    
    /**
     * @return string the internal structure of  the decorator as JSON
     */
    function toJSON() {
        $json = '{';
        $json .= "'field_id':". $this->field_id .",";
        $json .= "'value_id':". $this->value_id .",";
        $json .= "'r':". $this->r .",";
        $json .= "'g':". $this->g .",";
        $json .= "'b':". $this->b ."";
        $json .= '}';
        return $json;
    }
    
    /**
     * @return string
     */
    public function toHexa($r, $g, $b) {
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
    
    public static function toRGB($hex) {
        $delta = strlen($hex) == 4 ? 1 : 2;
        return array(
            hexdec(substr($hex, 1 + 0 * $delta, $delta)),
            hexdec(substr($hex, 1 + 1 * $delta, $delta)),
            hexdec(substr($hex, 1 + 2 * $delta, $delta)),
        );
    }
    
    /**
     * Save a decorator
     */
    public static function save($field_id, $value_id, $hexacolor) {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();
        list($r, $g, $b) = self::toRGB($hexacolor);
        $dao->save($field_id, $value_id, $r, $g, $b);
    }
    
    /**
     * Delete a decorator
     */
    public static function delete($field_id, $value_id) {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();
        $dao->delete($field_id, $value_id);
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root the node to which the Bind is attached (passed by reference)
     * @param int $val the id indentifing the value in the XML (different form $this->value_id)
     */
    public function exportToXML ($root, $val) {
        $child = $root->addChild('decorator');
        $child->addAttribute('REF', $val);
        $child->addAttribute('r', $this->r);
        $child->addAttribute('g', $this->g);
        $child->addAttribute('b', $this->b);
    }
}
?>
