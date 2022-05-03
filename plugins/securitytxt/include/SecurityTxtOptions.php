<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\SecurityTxt;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyString;

#[ConfigKeyCategory('security.txt (RFC 9116)')]
final class SecurityTxtOptions
{
    private function __construct()
    {
    }

    #[ConfigKey('Primary contact to use in the "security.txt" file')]
    #[ConfigKeyString]
    #[ConfigKeyHelp('The expected format is the one described in the RFC 9116 section 2.5.3')]
    public const CONTACT = 'security_txt_primary_contact';
}
