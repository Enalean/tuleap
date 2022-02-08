<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML;

use CardwallConfigXml;

final class XMLCardwallColumn
{
    /**
     * @readonly
     * @var string
     */
    private $label;
    /**
     * @readonly
     * @var string|null
     */
    private $id = null;
    /**
     * @readonly
     * @var string|null
     */
    private $tlp_color_name = null;
    /**
     * @readonly
     * @var string|null
     */
    private $bg_blue = null;
    /**
     * @readonly
     * @var string|null
     */
    private $bg_green = null;
    /**
     * @readonly
     * @var string|null
     */
    private $bg_red = null;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    /**
     * @psalm-mutation-free
     */
    public function withId(string $id): self
    {
        $new     = clone $this;
        $new->id = $id;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withTLPColorName(string $tlp_color_name): self
    {
        $new                 = clone $this;
        $new->tlp_color_name = $tlp_color_name;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withLegacyColorsName(string $bg_red, string $bg_green, string $bg_blue): self
    {
        $new           = clone $this;
        $new->bg_red   = $bg_red;
        $new->bg_green = $bg_green;
        $new->bg_blue  = $bg_blue;
        return $new;
    }

    public function export(\SimpleXMLElement $columns_node): \SimpleXMLElement
    {
        $column_node = $columns_node->addChild(CardwallConfigXml::NODE_COLUMN);
        $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_LABEL, $this->label);
        if ($this->id !== null) {
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_ID, $this->id);
        }

        if ($this->tlp_color_name !== null) {
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_TLP_COLOR_NAME, $this->tlp_color_name);
            return $column_node;
        }

        if ($this->bg_red !== null && $this->bg_green !== null && $this->bg_blue !== null) {
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_RED, $this->bg_red);
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_GREEN, $this->bg_green);
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_BLUE, $this->bg_blue);
        }

        return $column_node;
    }
}
