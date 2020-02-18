<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use Tuleap\Cryptography\ConcealedString;

class AccessKeyPresenter
{
    /**
     * @var \Tuleap\User\AccessKey\AccessKeyMetadataPresenter[]
     */
    public $access_keys;

    /**
     * @var \Tuleap\User\AccessKey\Scope\AccessKeyScopePresenter[]
     */
    public $access_key_scopes;

    /**
     * @var ?ConcealedString
     */
    public $last_access_key;

    /**
     * @var string
     */
    public $last_access_resolution;

    /**
     * @var bool
     */
    public $has_access_keys;

    public function __construct(array $access_key_scopes, array $access_keys, ?ConcealedString $last_access_key, string $last_access_resolution)
    {
        $this->access_key_scopes = $access_key_scopes;
        $this->access_keys = $access_keys;
        $this->last_access_key = $last_access_key;
        $this->last_access_resolution = $last_access_resolution;
        $this->has_access_keys = count($this->access_keys) > 0;
    }
}
