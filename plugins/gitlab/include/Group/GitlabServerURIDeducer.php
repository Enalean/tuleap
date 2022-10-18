<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final class GitlabServerURIDeducer
{
    public function __construct(private UriFactoryInterface $uri_factory)
    {
    }

    public function deduceServerURI(GroupLink $group_link): UriInterface
    {
        $uri = $this->uri_factory->createUri($group_link->web_url);
        return $uri->withPath('')->withFragment('')->withQuery('');
    }
}
