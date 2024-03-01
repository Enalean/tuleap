<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

namespace Tuleap\SVNCore\AccessControl;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\SVNCore\Cache\ParameterRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

final class SVNProjectAccessControllerTest extends TestCase
{
    /**
     * @var SVNLoginNameUserProvider&\PHPUnit\Framework\MockObject\Stub
     */
    private $user_retriever;

    protected function setUp(): void
    {
        $this->user_retriever = $this->createStub(SVNLoginNameUserProvider::class);
    }

    private function buildController(
        \ProjectManager $project_factory,
        CheckProjectAccess $check_project_access,
        bool $is_user_authentication_successful,
        int $cache_lifetime_minutes = ParameterRetriever::LIFETIME_DEFAULT,
    ): SVNProjectAccessController {
        $auth_method = new class ($is_user_authentication_successful, $this->user_retriever) implements SVNAuthenticationMethod
        {
            public function __construct(private bool $is_success, private SVNLoginNameUserProvider $user_retriever)
            {
            }

            public function isAuthenticated(string $login_name, ConcealedString $user_secret, \Project $project, ServerRequestInterface $request): ?\PFUser
            {
                if ($this->is_success) {
                    return $this->user_retriever->getUserFromSVNLoginName($login_name, $project);
                }
                return null;
            }
        };

        $cache_parameters = $this->createStub(ParameterRetriever::class);
        $cache_parameters->method('getParameters')->willReturn(new \Tuleap\SVNCore\Cache\Parameters($cache_lifetime_minutes));

        return new SVNProjectAccessController(
            HTTPFactoryBuilder::responseFactory(),
            new NullLogger(),
            new BasicAuthLoginExtractor(),
            $project_factory,
            $check_project_access,
            [$auth_method],
            $cache_parameters,
            $this->createStub(EmitterInterface::class),
        );
    }

    public function testUserCanAccessRepositoryWithValidCredentials(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willReturn($project);
        $check_project_access = CheckProjectAccessStub::withValidAccess();

        $controller = $this->buildController($project_factory, $check_project_access, true, 10);

        $user = UserTestBuilder::anActiveUser()->withUserName('valid_user_name')->build();
        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn($user);

        $request  = self::buildServerRequest($project->getUnixName(), 'valid_login_name', 'password');
        $response = $controller->handle($request);

        self::assertEquals(204, $response->getStatusCode());
        self::assertEquals('max-age=600', $response->getHeaderLine('Cache-Control'));
    }

    public function testRequestIsRejectedWhenAuthorizationHeaderIsMissing(): void
    {
        $controller = $this->buildController($this->createStub(\ProjectManager::class), CheckProjectAccessStub::withNotValidProject(), false);

        $request  = self::buildServerRequest('project', '', '')->withoutHeader('Authorization');
        $response = $controller->handle($request);

        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('Basic realm="Authentication is required to access the repository."', $response->getHeaderLine('WWW-Authenticate'));
    }

    public function testRequestIsRejectedWhenTuleapProjectNameHeaderIsMissing(): void
    {
        $controller = $this->buildController($this->createStub(\ProjectManager::class), CheckProjectAccessStub::withNotValidProject(), false);

        $request  = self::buildServerRequest('', 'username', 'pass')->withoutHeader('Tuleap-Project-Name');
        $response = $controller->handle($request);

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testRequestIsRejectedWhenUserCannotBeFoundFromItsLoginName(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willReturn($project);
        $controller = $this->buildController($project_factory, CheckProjectAccessStub::withNotValidProject(), false);

        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn(null);

        $request  = self::buildServerRequest('name', 'invalid_login_name', 'password');
        $response = $controller->handle($request);

        self::assertEquals(401, $response->getStatusCode());
    }

    public function testRequestIsRejectedWhenNoValidProjectCanBeFound(): void
    {
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willThrowException(new \Project_NotFoundException());
        $controller = $this->buildController($project_factory, CheckProjectAccessStub::withNotValidProject(), false);

        $user = UserTestBuilder::anActiveUser()->withUserName('valid_user_name')->build();
        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn($user);

        $request  = self::buildServerRequest('not_found', 'valid_login_name', 'password');
        $response = $controller->handle($request);

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testRequestIsRejectedWhenProjectIsSuspended(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(102)->withStatusSuspended()->build();
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willReturn($project);
        $controller = $this->buildController($project_factory, CheckProjectAccessStub::withValidAccess(), false);

        $user = UserTestBuilder::anActiveUser()->withUserName('valid_super_user_name')->build();
        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn($user);

        $request  = self::buildServerRequest('suspended', 'valid_login_name', 'password');
        $response = $controller->handle($request);

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testRequestIsRejectedWhenUserCannotAccessProject(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willReturn($project);
        $controller = $this->buildController($project_factory, CheckProjectAccessStub::withNotValidProject(), true);

        $user = UserTestBuilder::anActiveUser()->withUserName('valid_user_name')->build();
        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn($user);

        $request  = self::buildServerRequest($project->getUnixName(), 'valid_login_name', 'password');
        $response = $controller->handle($request);

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testRequestIsRejectedWhenUserCannotBeAuthenticated(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_factory = $this->createStub(\ProjectManager::class);
        $project_factory->method('getValidProjectByShortNameOrId')->willReturn($project);
        $controller = $this->buildController($project_factory, CheckProjectAccessStub::withValidAccess(), false);

        $user = UserTestBuilder::anActiveUser()->withUserName('valid_user_name')->build();
        $this->user_retriever->method('getUserFromSVNLoginName')->willReturn($user);

        $request  = self::buildServerRequest($project->getUnixName(), 'valid_login_name', 'wrong_password');
        $response = $controller->handle($request);

        self::assertEquals(401, $response->getStatusCode());
    }

    private static function buildServerRequest(string $project_name, string $username, string $user_secret): ServerRequestInterface
    {
        return (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $user_secret))
            ->withAttribute('project_name', $project_name);
    }
}
