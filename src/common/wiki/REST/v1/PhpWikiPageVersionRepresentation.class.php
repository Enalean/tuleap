<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\PhpWiki\REST\v1;

use Tuleap\REST\v1\PhpWikiPageRepresentation;
use WikiPageVersion;

class PhpWikiPageVersionRepresentation
{

    public const ROUTE = 'versions';

    /**
     * @var int {@type int}
     */
    public $version_id;

    /**
     * @var string {@type string}
     */
    public $uri;

    public function build(WikiPageVersion $version)
    {
        $this->version_id = $version->getVersionId();
        $this->uri        = PhpWikiPageRepresentation::ROUTE . '/' . $version->getPageId() . '/' . self::ROUTE . '?version_id=' . $this->version_id;
    }
}
