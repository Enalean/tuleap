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

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\OAuth2ServerCore\App\NewOAuth2App;
use Tuleap\ServerHostname;

final class MediaWikiNewOAuth2AppBuilder
{
    private const string OAUTH2_REDIRECT_ENDPOINT = '/mediawiki/_oauth/Special:TuleapLogin/callback';

    public function __construct(private SplitTokenVerificationStringHasher $hasher)
    {
    }

    public function buildMediawikiOAuth2App(): NewOAuth2App
    {
        return NewOAuth2App::fromSiteAdministrationAppData(
            sprintf('%s MediaWiki', \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)),
            ServerHostname::HTTPSUrl() . self::OAUTH2_REDIRECT_ENDPOINT,
            true,
            $this->hasher,
            MediawikiStandaloneService::SERVICE_SHORTNAME,
        );
    }
}
