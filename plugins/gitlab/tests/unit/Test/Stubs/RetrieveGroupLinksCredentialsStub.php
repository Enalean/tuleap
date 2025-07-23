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

use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\Token\RetrieveGroupLinksCredentials;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

final class RetrieveGroupLinksCredentialsStub implements RetrieveGroupLinksCredentials
{
    public function __construct(private Credentials $credentials)
    {
    }

    #[\Override]
    public function retrieveCredentials(GroupLink $group_link): Credentials
    {
        return $this->credentials;
    }

    public static function withDefaultCredentials(): self
    {
        return new self(CredentialsTestBuilder::get()->build());
    }
}
