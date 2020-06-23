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

/**
 * Drop-in replacement of "good old" box inherited from sourceforge
 *
 * In order to have the same output rendering than a "real" widget
 * you just have to enclose your current box based output into an
 * instance of this class.
 *
 * Example:
 * <pre>
 * $w = new StaticWidget("Title");
 * $w->setContent("Meaningful stuff, I hope so");
 * $w->display();
 * </pre>
 */
class Widget_Static extends Widget // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Title
     * @var String
     */
    protected $title   = "";

    /**
     * Content
     * @var String
     */
    protected $content = "";

    /**
     * Icon
     * @var String
     */
    protected $icon    = "";

    /**
     * Icon
     * @var String
     */
    protected $additional_class = "";

    /**
     * Constructor
     *
     * @param String $title Widget title
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Output the widget
     */
    public function display()
    {
        $GLOBALS['HTML']->displayStaticWidget($this);
    }

    /**
     * Title setter
     *
     * @param String $title title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Title getter
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Content setter
     *
     * @param String $content content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Content getter
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setAdditionalClass($additional_class)
    {
        $this->additional_class = $additional_class;
    }

    public function getAdditionalClass()
    {
        return $this->additional_class;
    }
}
