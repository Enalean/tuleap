<?php
/*
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

use Tuleap\Event\Dispatchable;

final class RedirectAfterLogin implements Dispatchable
{
    public const NAME = 'redirectAfterLogin';

    /**
     * @var \PFUser
     * @psalm-readonly
     */
    public $user;
    /**
     * @var string
     */
    private $return_to;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_pv2;

    public function __construct(\PFUser $user, string $return_to, bool $is_pv2)
    {
        $this->user = $user;
        $this->return_to = $return_to;
        $this->is_pv2 = $is_pv2;
    }

    public function getReturnTo(): string
    {
        return $this->return_to;
    }

    public function setReturnTo(string $return_to): void
    {
        $this->return_to = $return_to;
    }
}
