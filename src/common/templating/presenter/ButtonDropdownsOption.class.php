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

class Templating_Presenter_ButtonDropdownsOption
{

    private $url;
    private $selected;
    protected $label;
    protected $id;
    protected $li_parameters = array();

    public function __construct($id, $label, $selected, $url)
    {
        $this->id       = $id;
        $this->label    = $label;
        $this->selected = $selected;
        $this->url      = $url;
    }

    public function addLiParameter($parameter, $value)
    {
        $this->li_parameters[] = array(
            'parameter' => $parameter,
            'value'     => $value,
        );
        return $this;
    }

    public function setLiParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->addLiParameter($key, $value);
        }
        return $this;
    }

    public function simple()
    {
        return true;
    }

    public function submenu()
    {
        return false;
    }

    public function divider()
    {
        return false;
    }

    public function title()
    {
        return false;
    }

    public function id()
    {
        return $this->id;
    }

    public function label()
    {
        return $this->label;
    }

    public function selected()
    {
        return $this->selected;
    }

    public function url()
    {
        return $this->url;
    }

    public function data_toggle()
    {
    }

    public function li_parameters()
    {
        return $this->li_parameters;
    }
}
