<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../vendor/autoload.php';

use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DynamicCredentials\Credential\CredentialDAO;
use Tuleap\DynamicCredentials\Credential\CredentialIdentifierExtractor;
use Tuleap\DynamicCredentials\Credential\CredentialRemover;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;
use Tuleap\DynamicCredentials\Plugin\DynamicCredentialsSettings;
use Tuleap\DynamicCredentials\REST\ResourcesInjector;
use Tuleap\DynamicCredentials\Session\DynamicCredentialIdentifierStorage;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSession;
use Tuleap\DynamicCredentials\User\DynamicUser;
use Tuleap\DynamicCredentials\User\DynamicUserCreator;
use Tuleap\SVNCore\AccessControl\AfterLocalSVNLogin;
use Tuleap\SVNCore\AccessControl\BeforeSVNLogin;
use Tuleap\User\AfterLocalStandardLogin;
use Tuleap\User\BeforeStandardLogin;

class dynamic_credentialsPlugin extends Plugin implements PluginWithConfigKeys // @codingStandardsIgnoreLine
{
    public const NAME = 'dynamic_credentials';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-dynamic_credentials', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\DynamicCredentials\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(BeforeStandardLogin::NAME);
        $this->addHook(BeforeSVNLogin::NAME, BeforeStandardLogin::NAME);
        $this->addHook(AfterLocalStandardLogin::NAME);
        $this->addHook(AfterLocalSVNLogin::NAME, AfterLocalStandardLogin::NAME);
        $this->addHook(Event::USER_MANAGER_GET_USER_INSTANCE);
        $this->addHook('codendi_daily_start', 'dailyCleanup');

        return parent::getHooksAndCallbacks();
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(DynamicCredentialsSettings::class);
    }

    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    private function getIdentifierStorage(): DynamicCredentialIdentifierStorage
    {
        if (($_SERVER[LoaderScheduler::FASTCGI_DISABLE_SESSION_AUTOSTART_INSTRUCTION] ?? '') === 'true') {
            return new \Tuleap\DynamicCredentials\Session\DynamicCredentialStaticStorage();
        }
        if (session_status() === PHP_SESSION_NONE) {
            PHP_Session::start();
        }
        return new \Tuleap\DynamicCredentials\Session\DynamicCredentialSessionStorage();
    }

    public function beforeLogin(BeforeStandardLogin|BeforeSVNLogin $event): void
    {
        $credential_retriever = $this->getCredentialRetriever();
        $identifier_storage   = $this->getIdentifierStorage();
        $support_session      = new DynamicCredentialSession($identifier_storage, $credential_retriever);
        try {
            $support_session->initialize($event->getLoginName(), $event->getPassword());
        } catch (\Tuleap\DynamicCredentials\Credential\CredentialException $e) {
            return;
        }
        $user = UserManager::instance()->getUserById(DynamicUser::ID);
        if ($user) {
            $event->setUser($user);
        }
    }

    public function afterLocalLogin(AfterLocalStandardLogin|AfterLocalSVNLogin $event): void
    {
        if ((int) $event->user->getId() === DynamicUser::ID) {
            $event->refuseLogin(dgettext('tuleap-dynamic_credentials', 'Dynamic User cannot authenticate with login/password'));
        }
    }

    public function userManagerGetUserInstance(array $params)
    {
        if ((int) $params['row']['user_id'] !== DynamicUser::ID) {
            return;
        }

        $credential_retriever = $this->getCredentialRetriever();
        $identifier_storage   = $this->getIdentifierStorage();
        if (defined('IS_SCRIPT') && IS_SCRIPT && session_status() !== PHP_SESSION_NONE) {
            session_write_close();
        }

        $dynamic_session = new DynamicCredentialSession($identifier_storage, $credential_retriever);

        $support_user_creator = new DynamicUserCreator(
            $dynamic_session,
            UserManager::instance(),
            $this->getSettings()->getDynamicUserRealname(),
            function () {
                header('Location: /');
                exit();
            }
        );
        $params['user']       = $support_user_creator->getDynamicUser($params['row']);
    }

    public function dailyCleanup()
    {
        $credential_remover = new CredentialRemover(new CredentialDAO(), new CredentialIdentifierExtractor());
        $credential_remover->deleteExpired();
    }

    /**
     * @return CredentialRetriever
     */
    private function getCredentialRetriever()
    {
        return new CredentialRetriever(
            new CredentialDAO(),
            PasswordHandlerFactory::getPasswordHandler(),
            new CredentialIdentifierExtractor()
        );
    }

    private function getSettings(): DynamicCredentialsSettings
    {
        return new DynamicCredentialsSettings($this->getPluginInfo());
    }
}
