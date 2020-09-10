<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use SimpleXMLElement;

class BindDecoratorExporter
{
    public function exportToXML(
        SimpleXMLElement $root,
        string $val,
        bool $is_using_old_palette,
        ?string $r,
        ?string $g,
        ?string $b,
        ?string $tlp_color_name
    ): void {
        $child = $root->addChild('decorator');
        $child->addAttribute('REF', $val);

        $this->exportColorToXml($child, $is_using_old_palette, $r, $g, $b, $tlp_color_name);
    }

    public function exportNoneToXML(
        SimpleXMLElement $root,
        bool $is_using_old_palette,
        ?string $r,
        ?string $g,
        ?string $b,
        ?string $tlp_color_name
    ): void {
        $child = $root->addChild('decorator');
        $this->exportColorToXml($child, $is_using_old_palette, $r, $g, $b, $tlp_color_name);
    }

    private function exportColorToXml(
        SimpleXMLElement $child,
        bool $is_using_old_palette,
        ?string $r,
        ?string $g,
        ?string $b,
        ?string $tlp_color_name
    ): void {
        if ($is_using_old_palette && $r !== null && $g !== null && $b !== null) {
            $child->addAttribute('r', $r);
            $child->addAttribute('g', $g);
            $child->addAttribute('b', $b);
        } elseif ($tlp_color_name) {
            $child->addAttribute('tlp_color_name', $tlp_color_name);
        }
    }
}
