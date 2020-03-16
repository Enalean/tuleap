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

/**
 * @psalm-immutable
 */
final class SSHKeyPresenter
{
    /**
     * @var int
     */
    public $ssh_key_number;
    /**
     * @var string
     */
    public $ssh_key_value;
    /**
     * @var string
     */
    public $ssh_key_ellipsis_value;

    public function __construct(int $ssh_key_number, string $ssh_key_value)
    {
        $this->ssh_key_number         = $ssh_key_number;
        $this->ssh_key_value          = $ssh_key_value;
        $this->ssh_key_ellipsis_value = substr($ssh_key_value, 0, 50) . '…' . substr($ssh_key_value, -50);
    }
}
