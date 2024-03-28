<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Http\Client;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;

#[ConfigKeyCategory('Outbound HTTP requests')]
final class OutboundHTTPRequestSettings
{
    #[ConfigKey('CIDR ranges that can be reached by outbound HTTP requests')]
    #[ConfigKeyString('')]
    #[ConfigKeyValueValidator(CIDRRangesValidator::class)]
    public const ALLOW_RANGES = 'http_outbound_requests_allow_ranges';

    #[ConfigKey('CIDR ranges that cannot be reached by outbound HTTP requests if not allowed (extends the default deny list)')]
    #[ConfigKeyString('')]
    #[ConfigKeyValueValidator(CIDRRangesValidator::class)]
    public const DENY_RANGES = 'http_outbound_requests_deny_ranges';
}
