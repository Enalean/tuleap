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

namespace Tuleap\Gitlab\Test\Stubs;

use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Tuleap\Gitlab\API\BuildGitlabHttpClient;
use Tuleap\Gitlab\API\Credentials;

final class GitlabHTTPClientFactoryStub implements BuildGitlabHttpClient
{
    private function __construct(private ClientInterface $client_interface)
    {
    }

    #[\Override]
    public function buildHTTPClient(Credentials $gitlab_credentials): PluginClient
    {
        return new PluginClient($this->client_interface);
    }

    public static function buildWithClientInterface(ClientInterface $client_interface): self
    {
        return new self($client_interface);
    }
}
