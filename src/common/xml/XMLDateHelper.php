<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\xml;

use DateTimeImmutable;
use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

final class XMLDateHelper
{
    private const FORMAT = 'ISO8601';

    public static function addChild(SimpleXMLElement $parent_node, string $node_name, DateTimeImmutable $date): void
    {
        $cdata_factory = new XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $parent_node,
            $node_name,
            $date->format(DateTimeImmutable::ATOM),
            ['format' => self::FORMAT]
        );
    }

    /**
     * @throws InvalidDateException
     */
    public static function extractFromNode(SimpleXMLElement $xml): DateTimeImmutable
    {
        if ((string) $xml['format'] !== self::FORMAT) {
            throw new InvalidDateException('Invalid format for date. Should be ' . self::FORMAT);
        }

        $time = (string) $xml;
        $date = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            $time
        );

        if ($date === false) {
            throw new InvalidDateException('Invalid date (format not ISO8601?):' . $time);
        }

        return $date;
    }
}
