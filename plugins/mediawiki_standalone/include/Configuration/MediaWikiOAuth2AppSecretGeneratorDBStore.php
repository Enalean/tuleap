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

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\LastGeneratedClientSecret;

final class MediaWikiOAuth2AppSecretGeneratorDBStore implements MediaWikiOAuth2AppSecretGenerator
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private AppDao $oauth2_app_dao,
        private MediaWikiNewOAuth2AppBuilder $oauth2_app_builder,
        private SplitTokenVerificationStringHasher $hasher,
        private SplitTokenFormatter $split_token_formatter,
    ) {
    }

    #[\Override]
    public function generateOAuth2AppSecret(): LastGeneratedClientSecret
    {
        return $this->transaction_executor->execute(
            function (): LastGeneratedClientSecret {
                $app_id = $this->getExistingMediawikiOAuth2AppID();
                $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

                if ($app_id === null) {
                    $app    = $this->oauth2_app_builder->buildMediawikiOAuth2App();
                    $app_id = $this->oauth2_app_dao->create($app);
                    $secret = $app->getSecret();
                } else {
                    $this->oauth2_app_dao->updateSecret($app_id, $this->hasher->computeHash($secret));
                }

                return new LastGeneratedClientSecret(
                    $app_id,
                    $this->split_token_formatter->getIdentifier(
                        new SplitToken(
                            $app_id,
                            new SplitTokenVerificationString($secret->getString())
                        )
                    )
                );
            }
        );
    }

    private function getExistingMediawikiOAuth2AppID(): ?int
    {
        $apps = $this->oauth2_app_dao->searchSiteLevelApps(MediawikiStandaloneService::SERVICE_SHORTNAME);

        foreach ($apps as $app) {
            return $app['id'];
        }

        return null;
    }
}
