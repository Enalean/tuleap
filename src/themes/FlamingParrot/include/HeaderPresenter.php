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

use Tuleap\Layout\ThemeVariantColor;
use Tuleap\OpenGraph\OpenGraphPresenter;
use Tuleap\User\Account\Appearance\FaviconVariant;

class FlamingParrot_HeaderPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /** @var string */
    private $title;

    /** @var OpenGraphPresenter */
    public $open_graph;

    /** @var string */
    public $variant_color_code;
    public string $favicon_variant;

    public function __construct(
        PFUser $user,
        $title,
        OpenGraphPresenter $open_graph,
        \Tuleap\Layout\ThemeVariantColor $variant,
    ) {
        $this->title              = $title;
        $this->open_graph         = $open_graph;
        $this->variant_color_code = $variant->getHexaCode();


        $this->favicon_variant = FaviconVariant::shouldDisplayFaviconVariant($user)
            ? $variant->getName()
            : ThemeVariantColor::Orange->getName();
    }

    public function title(): string
    {
        return html_entity_decode($this->title);
    }
}
