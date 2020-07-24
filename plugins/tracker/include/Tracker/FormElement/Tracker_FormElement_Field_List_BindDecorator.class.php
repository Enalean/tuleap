<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-1009. All rights reserved.
 * Copyright (c) Enalean, 2011 - present. All Rights Reserved.
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
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorExporter;

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
     */
    public function decorateEdit($is_used_in_semantics): ColorpickerMountPointPresenter
    {
        $color = $this->getCurrentColor();
        $id    = 'decorator_' . $this->field_id . '_' . $this->value_id;

        return self::getColorPickerMountPoint($id, $this->value_id, $color, $is_used_in_semantics);
    }

    private static function getColorPickerMountPoint($decorator_id, $value_id, $current_color, $is_used_in_semantics): ColorpickerMountPointPresenter
    {
        $input_name = "bind[decorator][$value_id]";
        $input_id   = $decorator_id . '_field';

        return new ColorpickerMountPointPresenter(
            $current_color,
            $input_name,
            $input_id,
            $is_used_in_semantics
        );
    }

    public static function noDecoratorEdit(int $field_id, int $value_id, bool $is_used_in_semantics): ColorpickerMountPointPresenter
    {
        $id   = 'decorator_' . $field_id . '_' . $value_id;

        return self::getColorPickerMountPoint($id, $value_id, null, $is_used_in_semantics);
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
            [
                'field_id' => $this->field_id,
                'value_id' => $this->value_id,
                'r'        => $this->r,
                'g'        => $this->g,
                'b'        => $this->b
            ]
        );
    }

    /**
     * Delete a decorator
     */
    public static function delete($field_id, $value_id)
    {
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();
        $dao->delete((int) $field_id, (int) $value_id);
    }

    /**
     * @param int $val the id indentifing the value in the XML (different form $this->value_id)
     */
    public function exportToXML(SimpleXMLElement $root, $val): void
    {
        $this->getDecoratorExporter()->exportToXML(
            $root,
            $val,
            $this->isUsingOldPalette(),
            (string) $this->r,
            (string) $this->g,
            (string) $this->b,
            $this->tlp_color_name
        );
    }

    public function exportNoneToXML(SimpleXMLElement $root): void
    {
        $this->getDecoratorExporter()->exportNoneToXML(
            $root,
            $this->isUsingOldPalette(),
            (string) $this->r,
            (string) $this->g,
            (string) $this->b,
            $this->tlp_color_name
        );
    }

    private function getDecoratorExporter(): BindDecoratorExporter
    {
        return new BindDecoratorExporter();
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

    public function isUsingOldPalette(): bool
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
