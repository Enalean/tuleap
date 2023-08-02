<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Docman\View\Admin\FilenamePatternWarningsCollector;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\WebDAV\Authentication\AccessKey\WebDAVAccessKeyScope;
use Tuleap\Webdav\Authentication\HeadersSender;
use Tuleap\WebDAV\ServerBuilder;
use Tuleap\WebDAV\WebdavController;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class WebDAVPlugin extends Plugin
{
    public const LOG_IDENTIFIER = 'webdav_syslog';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-webdav', __DIR__ . '/../site-content');
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook(AccessKeyScopeBuilderCollector::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(FilenamePatternWarningsCollector::NAME);
    }

    public function getPluginInfo(): WebDAVPluginInfo
    {
        if (! $this->pluginInfo instanceof WebDAVPluginInfo) {
            $this->pluginInfo = new WebDAVPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['docman'];
    }

    public function filenamePatternWarningsCollector(FilenamePatternWarningsCollector $collector): void
    {
        if (! $this->isAllowed($collector->getProjectId())) {
            return;
        }

        if ($collector->getFilenamePattern()->isEnforced()) {
            $collector->addWarning(
                dgettext(
                    'tuleap-webdav',
                    'WebDAV is currently activated and is not compatible with filename pattern usage. Since you are currently enforcing a filename pattern, you are not able to interact with your documents via WebDAV.'
                )
            );
        } else {
            $collector->addInfo(
                dgettext(
                    'tuleap-webdav',
                    'WebDAV is currently activated and is not compatible with filename pattern usage. If you choose to enforce a filename pattern, you will not be able to interact with your documents via WebDAV.'
                )
            );
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addRoute(
            WebdavController::VERBS,
            WebdavController::getFastRoutePattern(),
            $this->getRouteHandler('routeWebdav'),
        );
    }

    public function routeWebdav(): DispatchableWithRequest
    {
        return new WebdavController(
            $this->getWebDAVAuthentication(),
            $this->getServerBuilder(),
        );
    }

    public function collectAccessKeyScopeBuilder(AccessKeyScopeBuilderCollector $collector): void
    {
        $collector->addAccessKeyScopeBuilder($this->buildAccessKeyScopeBuilder());
    }

    private function buildAccessKeyScopeBuilder(): AuthenticationScopeBuilder
    {
        return new AuthenticationScopeBuilderFromClassNames(
            WebDAVAccessKeyScope::class
        );
    }

    private function getServerBuilder(): ServerBuilder
    {
        return new ServerBuilder($this);
    }

    private function getWebDAVAuthentication(): WebDAVAuthentication
    {
        $user_manager     = UserManager::instance();
        $password_handler = PasswordHandlerFactory::getPasswordHandler();
        return new WebDAVAuthentication(
            $user_manager,
            new User_LoginManager(
                EventManager::instance(),
                $user_manager,
                $user_manager,
                new \Tuleap\User\PasswordVerifier($password_handler),
                new User_PasswordExpirationChecker(),
                $password_handler
            ),
            new HeadersSender(),
            new \Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator(
                new \Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer(
                    new \Tuleap\User\AccessKey\PrefixAccessKey()
                ),
                new AccessKeyVerifier(
                    new AccessKeyDAO(),
                    new SplitTokenVerificationStringHasher(),
                    $user_manager,
                    new AccessKeyScopeRetriever(
                        new AccessKeyScopeDAO(),
                        $this->buildAccessKeyScopeBuilder()
                    )
                ),
                WebDAVAccessKeyScope::fromItself(),
                BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER),
            )
        );
    }
}
