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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore\AccessControl;

use FastRoute\RouteCollector;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\SVNCore\Cache\ParameterDao;
use Tuleap\SVNCore\Cache\ParameterRetriever;
use Tuleap\User\PasswordVerifier;

final class SVNProjectAccessRouteDefinition
{
    private function __construct()
    {
    }

    public static function defineRoute(RouteCollector $route_collector, string $subversion_repo_path_prefix): void
    {
        $route_collector->addRoute(
            'POST',
            $subversion_repo_path_prefix . '/{project_name}[/{path:.*}]',
            [self::class, 'instantiateProjectAccessController']
        );
    }

    public static function instantiateProjectAccessController(): SVNProjectAccessController
    {
        $logger           = \BackendLogger::getDefaultLogger();
        $event_manager    = \EventManager::instance();
        $user_manager     = \UserManager::instance();
        $password_handler = new \StandardPasswordHandler();
        return new SVNProjectAccessController(
            HTTPFactoryBuilder::responseFactory(),
            $logger,
            new BasicAuthLoginExtractor(),
            $user_manager,
            \ProjectManager::instance(),
            new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), $event_manager),
            [
                new SVNTokenBasedAuthenticationMethod(
                    new \SVN_TokenHandler(new \SVN_TokenDao(), new \RandomNumberGenerator(), $password_handler),
                    $logger
                ),
                new SVNPasswordBasedAuthenticationMethod(
                    new \User_LoginManager(
                        $event_manager,
                        $user_manager,
                        new PasswordVerifier($password_handler),
                        new \User_PasswordExpirationChecker(),
                        $password_handler
                    ),
                    $logger
                ),
            ],
            new ParameterRetriever(new ParameterDao()),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware('svn_auth_operation')
        );
    }
}
