<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Psr\Http\Message\UriFactoryInterface;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Group\Token\GroupLinkTokenRetriever;
use Tuleap\Gitlab\Group\Token\RetrieveGroupLinksCredentials;

final class GroupLinkCredentialsRetriever implements RetrieveGroupLinksCredentials
{
    public function __construct(private UriFactoryInterface $uri_factory, private GroupLinkTokenRetriever $group_link_token_retriever)
    {
    }

    public function retrieveCredentials(GroupLink $group_link): Credentials
    {
        $token                = $this->group_link_token_retriever->retrieveToken($group_link);
        $gitlab_group_web_uri = $this->uri_factory->createUri($group_link->web_url);
        $gitlab_server        = $gitlab_group_web_uri->getScheme() . '://' . $gitlab_group_web_uri->getHost();
        return new Credentials($gitlab_server, $token);
    }
}
