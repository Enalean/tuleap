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

namespace Tuleap\OnlyOffice\Administration;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeySecretValidator;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;

#[ConfigKeyCategory('ONLYOFFICE')]
final class OnlyOfficeDocumentServerSettings
{
    #[ConfigKey('URL of the ONLYOFFICE document server')]
    #[ConfigKeyString]
    #[ConfigKeyValueValidator(OnlyOfficeServerUrlValidator::class)]
    public const URL = 'onlyoffice_document_server_url';

    #[ConfigKey('JWT secret of the ONLYOFFICE document server')]
    #[ConfigKeySecret]
    #[ConfigKeySecretValidator(OnlyOfficeSecretKeyValidator::class)]
    public const SECRET = 'onlyoffice_document_server_secret';

    private function __construct()
    {
    }
}
