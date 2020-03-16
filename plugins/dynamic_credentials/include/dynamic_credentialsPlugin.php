<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\DynamicCredentials\Credential\CredentialDAO;
use Tuleap\DynamicCredentials\Credential\CredentialIdentifierExtractor;
use Tuleap\DynamicCredentials\Credential\CredentialRemover;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;
use Tuleap\DynamicCredentials\REST\ResourcesInjector;
use Tuleap\DynamicCredentials\Session\DynamicCredentialSession;
use Tuleap\DynamicCredentials\User\DynamicUser;
use Tuleap\DynamicCredentials\User\DynamicUserCreator;

class dynamic_credentialsPlugin extends Plugin // @codingStandardsIgnoreLine
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
        $this->addHook(Event::SESSION_BEFORE_LOGIN);
        $this->addHook(Event::SESSION_AFTER_LOGIN);
        $this->addHook(Event::USER_MANAGER_GET_USER_INSTANCE);
        $this->addHook('codendi_daily_start', 'dailyCleanup');

        return parent::getHooksAndCallbacks();
    }

    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function sessionBeforeLogin(array $params)
    {
        $credential_retriever = $this->getCredentialRetriever();
        if (session_status() === PHP_SESSION_NONE) {
            PHP_Session::start();
        }
        $support_session = new DynamicCredentialSession($_SESSION, $credential_retriever);
        try {
            $support_session->initialize($params['loginname'], $params['passwd']);
        } catch (\Tuleap\DynamicCredentials\Credential\CredentialException $e) {
            return;
        }
        $params['auth_success'] = true;
        $params['auth_user_id'] = DynamicUser::ID;
    }

    public function sessionAfterLogin(array $params)
    {
        if ((int) $params['user']->getId() === DynamicUser::ID) {
            $params['allow_codendi_login'] = false;
        }
    }

    public function userManagerGetUserInstance(array $params)
    {
        if ((int) $params['row']['user_id'] !== DynamicUser::ID) {
            return;
        }

        $credential_retriever = $this->getCredentialRetriever();

        if (session_status() === PHP_SESSION_NONE) {
            PHP_Session::start();
            if (defined('IS_SCRIPT') && IS_SCRIPT) {
                session_write_close();
            }
        }

        $dynamic_session      = new DynamicCredentialSession($_SESSION, $credential_retriever);
        $user_realname        = $this->getPluginInfo()->getPropertyValueForName('dynamic_user_realname');

        $support_user_creator = new DynamicUserCreator(
            $dynamic_session,
            UserManager::instance(),
            $user_realname,
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
}
