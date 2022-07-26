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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\OAuth2ServerCore\App\ClientIdentifier;
use Tuleap\ServerHostname;

final class LocalSettingsFactory implements LocalSettingsRepresentationBuilder
{
    public function __construct(
        private MediaWikiOAuth2AppSecretGenerator $oauth2_app_generator,
        private MediaWikiSharedSecretGenerator $shared_secret_generator,
    ) {
    }

    public function generateTuleapLocalSettingsRepresentation(): LocalSettingsRepresentation
    {
        $oauth2_secret = $this->oauth2_app_generator->generateOAuth2AppSecret();

        return new LocalSettingsRepresentation(
            $this->shared_secret_generator->generateSharedSecret(),
            ServerHostname::HTTPSUrl(),
            ClientIdentifier::fromLastGeneratedClientSecret($oauth2_secret)->toString(),
            $oauth2_secret->getSecret(),
            \ForgeConfig::get(\ForgeAccess::CONFIG, \ForgeAccess::RESTRICTED),
            \ForgeConfig::get('sys_supported_languages', 'en_US'),
        );
    }
}
