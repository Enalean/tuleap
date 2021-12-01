<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\REST\v1;

use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
final class ConcealedBotApiTokenPatchRepresentation
{
    /**
     * @var int
     */
    public $gitlab_integration_id;

    /**
     * @var ConcealedString
     */
    public $gitlab_bot_api_token;

    public function __construct(
        int $gitlab_integration_id,
        ConcealedString $gitlab_bot_api_token,
    ) {
        $this->gitlab_integration_id = $gitlab_integration_id;
        $this->gitlab_bot_api_token  = $gitlab_bot_api_token;
    }
}
