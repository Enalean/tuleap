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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\Token\InsertGroupLinkToken;

final class InsertGroupLinkTokenStub implements InsertGroupLinkToken
{
    private int $number_of_call;

    private function __construct()
    {
        $this->number_of_call = 0;
    }

    #[\Override]
    public function insertToken(GroupLink $gitlab_group, ConcealedString $token): void
    {
        $this->number_of_call++;
    }

    public function getNumberOfCallInsertTokenMethod(): int
    {
        return $this->number_of_call;
    }

    public static function build(): self
    {
        return new self();
    }
}
