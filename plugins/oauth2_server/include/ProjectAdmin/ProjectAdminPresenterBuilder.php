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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\ProjectAdmin;

use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\PrefixOAuth2ClientSecret;

class ProjectAdminPresenterBuilder
{
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var LastCreatedOAuth2AppStore
     */
    private $last_created_app_store;

    public function __construct(AppFactory $app_factory, LastCreatedOAuth2AppStore $last_created_app_store)
    {
        $this->app_factory            = $app_factory;
        $this->last_created_app_store = $last_created_app_store;
    }

    public static function buildSelf(): self
    {
        $storage =& $_SESSION ?? [];
        return new self(
            new AppFactory(new AppDao(), \ProjectManager::instance()),
            new LastCreatedOAuth2AppStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            )
        );
    }

    public function build(\CSRFSynchronizerToken $csrf_token, \Project $project): ProjectAdminPresenter
    {
        $apps       = $this->app_factory->getAppsForProject($project);
        $presenters = [];
        foreach ($apps as $app) {
            $presenters[] = new AppPresenter(
                $app->getId(),
                $app->getName(),
                $app->getRedirectEndpoint(),
                ClientIdentifier::fromOAuth2App($app)->toString(),
                $app->isUsingPKCE()
            );
        }

        return new ProjectAdminPresenter($presenters, $csrf_token, $project, $this->getLastCreatedAppPresenter());
    }

    private function getLastCreatedAppPresenter(): ?LastCreatedOAuth2AppPresenter
    {
        $last_created_app = $this->last_created_app_store->getLastCreatedApp();
        if ($last_created_app === null) {
            return null;
        }
        return new LastCreatedOAuth2AppPresenter(
            ClientIdentifier::fromLastCreatedOAuth2App($last_created_app)->toString(),
            $last_created_app->getSecret()
        );
    }
}
