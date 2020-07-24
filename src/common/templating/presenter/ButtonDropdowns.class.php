<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Templating_Presenter_ButtonDropdowns
{

    private $id;
    private $label;
    /**
     * @var Templating_Presenter_ButtonDropdownsOption[]
     */
    private $options;
    private $icon = 'fa fa-cog';
    private $class_names = [];

    public function __construct($id, $label, array $options)
    {
        $this->label   = $label;
        $this->options = $options;
        $this->id      = $id;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    public function id()
    {
        return $this->id;
    }

    public function label()
    {
        return $this->label;
    }

    public function options()
    {
        return $this->options;
    }

    public function icon()
    {
        return $this->icon;
    }

    public function class_names()
    {
        return $this->class_names;
    }

    protected function addClassName($classname)
    {
        $this->class_names[] = $classname;
    }
}
