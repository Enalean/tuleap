<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\XML;

final class XMLUser
{
    private const FORMAT_USERNAME = 'username';
    private const FORMAT_LDAP     = 'ldap';
    private const FORMAT_ID       = 'id';
    private const FORMAT_EMAIL    = 'email';

    /**
     * @var self::FORMAT_*
     * @readonly
     */
    private $format;
    /**
     * @readonly
     */
    public string $name;

    /**
     * @param self::FORMAT_* $format
     */
    public function __construct(string $format, string $name)
    {
        $this->format = $format;
        $this->name   = $name;
    }

    public static function buildUsername(string $name): self
    {
        return new self(self::FORMAT_USERNAME, $name);
    }

    public static function buildUsernameFromUser(\PFUser $user): self
    {
        return new self(self::FORMAT_USERNAME, $user->getUserName());
    }

    public function export(string $node_name, \SimpleXMLElement $parent_node): \SimpleXMLElement
    {
        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        return $cdata_factory->insertWithAttributes(
            $parent_node,
            $node_name,
            $this->name,
            ['format' => $this->format]
        );
    }
}
