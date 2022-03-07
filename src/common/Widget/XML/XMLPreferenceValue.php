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

namespace Tuleap\Widget\XML;

class XMLPreferenceValue
{
    private function __construct(private string $name, private string $ref, private string $text)
    {
    }

    public static function ref(string $name, string $ref): self
    {
        return new self($name, $ref, '');
    }

    public static function text(string $name, string $text): self
    {
        return new self($name, '', $text);
    }

    public function export(\SimpleXMLElement $xml): void
    {
        if ($this->ref) {
            $value = $xml->addChild('reference');
            $value->addAttribute('name', $this->name);
            $value->addAttribute('REF', $this->ref);
            return;
        }

        $cdata_section_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_section_factory->insertWithAttributes($xml, 'value', $this->text, ['name' => $this->name]);
    }
}
