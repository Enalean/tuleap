<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\XML;

use Tuleap\Widget\XML\XMLWidget;

final class XMLColumn
{
    /**
     * @var XMLWidget[]
     * @psalm-readonly
     */
    private array $widgets = [];

    public function export(\SimpleXMLElement $xml): void
    {
        if (empty($this->widgets)) {
            return;
        }

        $column = $xml->addChild('column');

        foreach ($this->widgets as $widget) {
            $widget->export($column);
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function withWidget(XMLWidget $widget): self
    {
        $new            = clone $this;
        $new->widgets[] = $widget;

        return $new;
    }
}
