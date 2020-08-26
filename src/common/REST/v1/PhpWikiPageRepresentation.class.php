<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST\v1;

use Tuleap\PHPWiki\WikiPage;

/**
 * @psalm-immutable
 */
class PhpWikiPageRepresentation
{

    public const ROUTE = 'phpwiki';

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $uri;

    /**
     * @var string {@type string}
     */
    public $name;

    protected function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->uri  = self::ROUTE . '/' . $id;
        $this->name = $name;
    }

    public static function build(WikiPage $page): PhpWikiPageRepresentation
    {
        return new self((int) $page->getId(), $page->getPagename());
    }
}
