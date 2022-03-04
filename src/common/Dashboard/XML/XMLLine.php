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

final class XMLLine
{
    /**
     * @var XMLColumn[]
     * @psalm-readonly
     */
    private array $columns = [];

    private function __construct(private string $layout)
    {
    }

    public static function withDefaultLayout(): self
    {
        return new self("");
    }

    public static function withLayout(string $layout): self
    {
        return new self($layout);
    }

    public function export(\SimpleXMLElement $xml): void
    {
        if (empty($this->columns)) {
            return;
        }

        $line = $xml->addChild('line');
        if ($this->layout) {
            $line->addAttribute('layout', $this->layout);
        }

        foreach ($this->columns as $column) {
            $column->export($line);
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function withColumn(XMLColumn $column): self
    {
        $new            = clone $this;
        $new->columns[] = $column;

        return $new;
    }
}
