<?php
/*
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\OpenGraph\OpenGraphPresenter;

class FlamingParrot_HeaderPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /** @var string */
    private $title;

    /** @var string */
    private $img_root;

    /** @var OpenGraphPresenter */
    public $open_graph;

    /** @var string */
    public $variant;

    /** @var string */
    public $variant_color_code;

    public function __construct(
        $title,
        $img_root,
        OpenGraphPresenter $open_graph,
        \Tuleap\Layout\ThemeVariantColor $variant,
    ) {
        $this->title              = $title;
        $this->img_root           = $img_root;
        $this->open_graph         = $open_graph;
        $this->variant            = $variant->value;
        $this->variant_color_code = $variant->getHexaCode();
    }

    public function title()
    {
        return html_entity_decode($this->title);
    }

    public function imgRoot()
    {
        return $this->img_root;
    }
}
