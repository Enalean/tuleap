<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class KASS_HeaderPresenter {

    /** @var string */
    private $title;

    /** @var string */
    private $img_root;

    /** @var string */
    private $javascript_elements;

    /** @var string */
    private $stylesheet_elements;

    /** @var string */
    private $syndication_elements;

    function __construct(
        $title,
        $img_root,
        $javascript_elements,
        $stylesheet_elements,
        $syndication_elements
    ) {
        $this->title                = $title;
        $this->img_root             = $img_root;
        $this->javascript_elements  = $javascript_elements;
        $this->stylesheet_elements  = $stylesheet_elements;
        $this->syndication_elements = $syndication_elements;
    }

    public function title() {
        return $this->title;
    }

    public function imgRoot() {
        return $this->img_root;
    }

    public function javascriptElements() {
        return $this->javascript_elements;
    }

    public function stylesheetElements() {
        return $this->stylesheet_elements;
    }

    public function syndicationElements() {
        return $this->syndication_elements;
    }

}

?>