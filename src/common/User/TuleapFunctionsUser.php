<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\User;

use Tuleap\ServerHostname;

final class TuleapFunctionsUser extends \PFUser
{
    public const ID = 70;

    public function __construct()
    {
        $email_domain = \ForgeConfig::get('sys_default_mail_domain');
        if (! $email_domain) {
            $email_domain = ServerHostname::rawHostname();
        }

        parent::__construct([
            'user_id' => self::ID,
            'email'   => 'noreply@' . $email_domain,
        ]);
    }
}
