<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved.
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

class Tracker_FormElement_Field_List_BindDecorator
{
    /**
     * @var int
     */
    public $field_id;
    /**
     * @var int
     */
    public $value_id;
    /**
     * @var int
     */
    public $r;
    /**
     * @var int
     */
    public $g;
    /**
     * @var int
     */
    public $b;

    /** @var string */
    public $tlp_color_name;

    public function __construct($field_id, $value_id, $r, $g, $b, $tlp_color_name)
    {
        $this->field_id       = $field_id;
        $this->value_id       = $value_id;
        $this->r              = $r;
        $this->g              = $g;
        $this->b              = $b;
        $this->tlp_color_name = $tlp_color_name;
    }

    /**
     * @param string $color
     * @return bool
     */
    public static function isHexaColor($color)
    {
        return strpos($color, '#') !== false;
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

        if (! $this->isUsingOldPalette()) {
            $html .= self::fetchSquareImage('blank16x16.png', [
                'title' => $value,
                'class' => 'colorpicker-preview-' . $this->tlp_color_name
            ]);
        } else {
            $html .= self::fetchSquareColor('', $value, '', $this->r, $this->g, $this->b);
        }

        if ($full) {
            $html .= ' '. $value .'</span>';
        }
        return $html;
    }

    public function decorateSelectOptionWithStyles() {
        if (! $this->isUsingOldPalette()) {
            return [
                'classes'       => 'select-option-colored-' . $this->tlp_color_name,
                'inline-styles' => ''
            ];
        }

        return [
            'classes'       => '',
            'inline-styles' => 'border-left: 16px solid '. ColorHelper::RGBToHexa($this->r, $this->g, $this->b) .';'
        ];
    }

    /**
     * Display the color and allow the user to edit it
     * @param string $value The value to decorate Don't forget to html-purify.
     * @param boolean $full false if you want only the decoration
     * @return string html
     */
    public function decorateEdit() {
        $html  = '';
        $color = $this->getCurrentColor();
        $id    = 'decorator_'. $this->field_id .'_'. $this->value_id;
        $html .= self::getColorPickerMountPoint($id, $this->value_id, $color);

        return $html;
    }

    private static function getColorPickerMountPoint($decorator_id, $value_id, $current_color)
    {
        $switch_old_palette_label     = dgettext('tuleap-tracker', 'Switch to old colors');
        $switch_default_palette_label = dgettext('tuleap-tracker', 'Switch to default colors');

        $input_name = "bind[decorator][$value_id]";
        $input_id   = $decorator_id . '_field';

        return '
            <div class="vue-colorpicker-mount-point"
                data-input-name="'. $input_name .'"
                data-input-id="'. $input_id .'"
                data-current-color="'. $current_color . '"
                data-switch-default-palette-label="' . $switch_default_palette_label . '"
                data-switch-old-palette-label="' . $switch_old_palette_label . '"
            ></div>
        ';
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
        $html .= self::getColorPickerMountPoint($id, $value_id, null);
        return $html;
    }

    protected static function fetchSquareColor($id, $title, $classname, $r, $g, $b, $img = 'blank16x16.png') {
        $html = '';
        $bgcolor = '';

        if ($r !== null && $g !== null && $b !== null ) {
            $bgcolor .= "background-color:rgb($r, $g, $b);";
        }

        $html .= self::fetchSquareImage($img, [
            'id'     => $id,
            'width'  => '16px',
            'height' => '16px',
            'style'  => 'vertical-align:middle; '. $bgcolor,
            'title'  => $title,
            'class'  => $classname
        ]);
        return $html;
    }

    private static function fetchSquareImage($image, array $image_styles)
    {
        return $GLOBALS['HTML']->getImage($image, $image_styles);
    }

    /**
     * @return string the internal structure of  the decorator as JSON
     */
    function toJSON() {
        return json_encode(
            array(
                'field_id' => $this->field_id,
                'value_id' => $this->value_id,
                'r'        => $this->r,
                'g'        => $this->g,
                'b'        => $this->b
            )
        );
    }

    /**
     * Save a decorator
     */
    public static function save($field_id, $value_id, $color) {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();

        if (! self::isHexaColor($color)) {
            return $dao->saveTlpColor($value_id, $color);
        }

        list($r, $g, $b) = ColorHelper::HexaToRGB($color);
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
    public function exportToXML(SimpleXMLElement $root, $val)
    {
        $child = $root->addChild('decorator');
        $child->addAttribute('REF', $val);

        if ($this->isUsingOldPalette()) {
            $child->addAttribute('r', $this->r);
            $child->addAttribute('g', $this->g);
            $child->addAttribute('b', $this->b);
        } else {
            $child->addAttribute('tlp_color_name', $this->tlp_color_name);
        }
    }

    /**
     * @return string rgb(234, 254, 123) or $default if not defined
     */
    public function css($default = 'transparent') {
        if ($this->r !== null && $this->g !== null && $this->b !== null ) {
            return 'rgb('. (int)$this->r .', '. (int)$this->g .', '. (int)$this->b .');';
        }
        return $default;
    }

    /**
     * @return bool
     */
    public function isDark() {
        if ($this->r !== null && $this->g !== null && $this->b !== null ) {
            return (0.3 * $this->r + 0.59 * $this->g + 0.11 * $this->b) < 128;
        }
        return false;
    }

    public function isUsingOldPalette()
    {
        return $this->tlp_color_name === null && $this->r !== null && $this->g !== null && $this->b !== null;
    }

    public function getCurrentColor()
    {
        if (! $this->isUsingOldPalette()) {
            return $this->tlp_color_name;
        }

        return ColorHelper::RGBToHexa($this->r, $this->g, $this->b);
    }
}
