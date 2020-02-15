<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

/* abstract */class Docman_View_ItemDetailsSection
{

    public $id;
    public $title;
    public $item;
    /**
     * @var string
     */
    protected $url;
    public $hp;

    public function __construct($item, string $url, $id, $title)
    {
        $this->id     = $id;
        $this->title  = $title;
        $this->item   = $item;
        $this->url    = $url;
        $this->hp     = Codendi_HTMLPurifier::instance();
    }

    public function getId()
    {
        return $this->id;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getContent($params = [])
    {
        return '';
    }
}
