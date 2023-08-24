<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\DB;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeyString;

final class DBAuthUserConfig
{
    #[ConfigKey('Database user for SVN authentication')]
    #[ConfigKeyHelp('On some platforms it is also used for local accounts')]
    #[ConfigKeyString('dbauthuser')]
    public const USER = 'sys_dbauth_user';

    #[ConfigKey('Password for `' . self::USER . '`')]
    #[ConfigKeyString]
    #[ConfigKeySecret]
    public const PASSWORD = 'sys_dbauth_passwd';
}
