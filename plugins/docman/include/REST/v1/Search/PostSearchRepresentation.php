<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\Docman\REST\v1\SearchResource;

/**
 * @psalm-immutable
 */
final class PostSearchRepresentation
{
    /**
     * @var string search in all string properties {@from body} {@required false}
     */
    public string $global_search = '';

    /**
     * @var string type of item {@from body} {@required false} {@choice folder,file,link,embedded,wiki,empty}
     */
    public string $type = '';

    /**
     * @var string title of item {@from body} {@required false}
     */
    public string $title = '';

    /**
     * @var string description of item {@from body} {@required false}
     */
    public string $description = '';

    /**
     * @var string owner of item {@from body} {@required false}
     */
    public string $owner = '';

    /**
     * @var SearchDateRepresentation {@type \Tuleap\Docman\REST\v1\Search\SearchDateRepresentation} creation date of item {@from body} {@required false}
     */
    public ?SearchDateRepresentation $create_date = null;

    /**
     * @var SearchDateRepresentation {@type \Tuleap\Docman\REST\v1\Search\SearchDateRepresentation} update date of item {@from body} {@required false}
     */
    public ?SearchDateRepresentation $update_date = null;

    /**
     * @var SearchDateRepresentation {@type \Tuleap\Docman\REST\v1\Search\SearchDateRepresentation} obsolescence date of item {@from body} {@required false}
     */
    public ?SearchDateRepresentation $obsolescence_date = null;

    /**
     * @var string status of item {@from body} {@required false} {@choice none,draft,approved,rejected}
     */
    public string $status = '';

    /**
     * @var array {@type \Tuleap\Docman\REST\v1\Search\CustomPropertyRepresentation} {@from body} {@required false}
     */
    public array $custom_properties = [];

    /**
     * @var int limit {@from body} {@required false} {@min 0} {@max 50}
     */
    public int $limit = SearchResource::MAX_LIMIT;

    /**
     * @var int offset {@from body} {@required false} {@min 0}
     */
    public int $offset = 0;
}
