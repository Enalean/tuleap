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

use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;

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
     *
     * @deprecated This function is unsafe, using it for anything new must be carefully
     * considered and should probably be avoided
     *
     * @param string $value The value to decorate Don't forget to html-purify.
     * @param bool $full false if you want only the decoration
     * @return string html
     */
    public function decorate($value, $full = true)
    {
        $html = '';
        if ($full) {
            $html .= '<span style="white-space:nowrap;">';
        }

        if (! $this->isUsingOldPalette()) {
            $html .= self::fetchSquareImage('blank16x16.png', [
                'title' => $value,
                'class' => \Codendi_HTMLPurifier::instance()->purify('colorpicker-preview-' . $this->tlp_color_name)
            ]);
        } else {
            $html .= self::fetchSquareColor('', $value, '', $this->r, $this->g, $this->b);
        }

        if ($full) {
            $html .= ' ' . $value . '</span>';
        }
        return $html;
    }

    /**
     * @deprecated This function is unsafe, using it for anything new must be carefully
     * considered and should probably be avoided
     */
    public function decorateSelectOptionWithStyles()
    {
        if (! $this->isUsingOldPalette()) {
            return [
                'classes'       => \Codendi_HTMLPurifier::instance()->purify('select-option-colored-' . $this->tlp_color_name),
                'inline-styles' => ''
            ];
        }

        $hexa_color = ColorHelper::RGBToHexa($this->r, $this->g, $this->b);
        if (preg_match('/^#([a-fA-F0-9]{3}){1,2}$/', $hexa_color) !== 1) {
            $hexa_color = '';
        }

        return [
            'classes'       => '',
            'inline-styles' => 'border-left: 16px solid ' . $hexa_color . ';'
        ];
    }

    /**
     * Display the color and allow the user to edit it
     * @return string html
     */
    public function decorateEdit($is_used_in_semantics)
    {
        $html  = '';
        $color = $this->getCurrentColor();
        $id    = 'decorator_' . $this->field_id . '_' . $this->value_id;
        $html .= self::getColorPickerMountPoint($id, $this->value_id, $color, $is_used_in_semantics);

        return $html;
    }

    private static function getColorPickerMountPoint($decorator_id, $value_id, $current_color, $is_used_in_semantics)
    {
        $input_name = "bind[decorator][$value_id]";
        $input_id   = $decorator_id . '_field';

        $renderer = TemplateRendererFactory::build()->getRenderer(
            TRACKER_TEMPLATE_DIR  . '/colorpicker/'
        );

        return $renderer->renderToString(
            'colorpicker-mount-point',
            new ColorpickerMountPointPresenter(
                $current_color,
                $input_name,
                $input_id,
                $is_used_in_semantics
            )
        );
    }

    /**
     * Display the transparent color and allow the user to edit it
     * @param string $value_id The value to decorate Don't forget to html-purify.
     * @param bool $is_used_in_semantics True if the field is used in a semantic
     * @return string html
     */
    public static function noDecoratorEdit($field_id, $value_id, $is_used_in_semantics)
    {
        $html = '';
        $id   = 'decorator_' . $field_id . '_' . $value_id;

        $html .= self::getColorPickerMountPoint($id, $value_id, null, $is_used_in_semantics);
        return $html;
    }

    protected static function fetchSquareColor($id, $title, $classname, $r, $g, $b, $img = 'blank16x16.png')
    {
        $html = '';
        $bgcolor = '';

        if ($r !== null && $g !== null && $b !== null) {
            $r = (int) $r;
            $g = (int) $g;
            $b = (int) $b;
            $bgcolor .= "background-color:rgb($r, $g, $b);";
        }

        $html .= self::fetchSquareImage($img, [
            'id'     => $id,
            'width'  => '16px',
            'height' => '16px',
            'style'  => 'vertical-align:middle; ' . $bgcolor,
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
    public function toJSON()
    {
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
    public static function save($field_id, $value_id, $color)
    {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();

        if (! self::isHexaColor($color)) {
            return $dao->saveTlpColor($value_id, $color);
        }

        list($r, $g, $b) = ColorHelper::HexaToRGB($color);
        $dao->save($field_id, $value_id, $r, $g, $b);
    }

    public static function update($field_id, $value_id, $color)
    {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();

        if (! self::isHexaColor($color)) {
            return $dao->updateTlpColor($value_id, $color);
        }

        list($r, $g, $b) = ColorHelper::HexaToRGB($color);
        $dao->updateColor($field_id, $value_id, $r, $g, $b);
    }

    /**
     * Delete a decorator
     */
    public static function delete($field_id, $value_id)
    {
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
    public function css($default = 'transparent')
    {
        if ($this->r !== null && $this->g !== null && $this->b !== null) {
            return 'rgb(' . (int) $this->r . ', ' . (int) $this->g . ', ' . (int) $this->b . ');';
        }
        return $default;
    }

    /**
     * @return bool
     */
    public function isDark()
    {
        if ($this->r !== null && $this->g !== null && $this->b !== null) {
            return (0.3 * $this->r + 0.59 * $this->g + 0.11 * $this->b) < 128;
        }
        return false;
    }

    public function isUsingOldPalette()
    {
        return ! $this->isTlpColorNamedDefined() && $this->isLegacyColorDefined();
    }

    private function isTlpColorNamedDefined()
    {
        return (string) $this->tlp_color_name !== '';
    }

    private function isLegacyColorDefined()
    {
        return $this->r !== null && $this->g !== null && $this->b !== null;
    }

    public function getCurrentColor()
    {
        if (! $this->isUsingOldPalette()) {
            return $this->tlp_color_name;
        }

        return ColorHelper::RGBToHexa($this->r, $this->g, $this->b);
    }
}
