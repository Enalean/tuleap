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

namespace Tuleap\OAuth2Server\User\Account;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\AuthorizationServer\OAuth2ScopeDefinitionPresenter;
use Tuleap\OAuth2Server\User\AuthorizedScopeFactory;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\AccountTabPresenterCollection;

final class AppsPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AppsPresenterBuilder */
    private $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppFactory
     */
    private $app_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizedScopeFactory
     */
    private $authorized_scope_factory;

    protected function setUp(): void
    {
        $this->dispatcher               = $this->createMock(EventDispatcherInterface::class);
        $this->app_factory              = $this->createMock(AppFactory::class);
        $this->authorized_scope_factory = $this->createMock(AuthorizedScopeFactory::class);
        $this->builder                  = new AppsPresenterBuilder(
            $this->dispatcher,
            $this->app_factory,
            $this->authorized_scope_factory
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testBuildTransformsAppsIntoPresenters(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->dispatcher->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(AccountTabPresenterCollection::class))
            ->willReturnArgument(0);
        $jenkins_app    = new OAuth2App(
            1,
            'Jenkins',
            'https://example.com',
            true,
            new \Project(['group_id' => 101, 'group_name' => 'Public project'])
        );
        $custom_app     = new OAuth2App(
            2,
            'My Custom REST Consumer',
            'https://example.com',
            true,
            new \Project(['group_id' => 102, 'group_name' => 'Private project'])
        );
        $site_level_app = new OAuth2App(
            3,
            'Site level app',
            'https://site-level.example.com',
            true,
            null
        );
        $this->app_factory->expects(self::once())->method('getAppsAuthorizedByUser')
            ->with($user)
            ->willReturn([$jenkins_app, $custom_app, $site_level_app]);

        $foobar_scope    = $this->buildFooBarScopeDefinition();
        $typevalue_scope = $this->buildTypeValueScopeDefinition();
        $this->authorized_scope_factory->method('getAuthorizedScopes')
            ->willReturnCallback(
                fn(\PFUser $received_user, OAuth2App $app): array => match (true) {
                    $received_user === $user && $app === $jenkins_app => [$foobar_scope],
                    $received_user === $user && $app === $custom_app => [$foobar_scope, $typevalue_scope],
                    $received_user === $user && $app === $site_level_app => [$foobar_scope],
                }
            );
        $csrf_presenter = CSRFSynchronizerTokenPresenter::fromToken(AccountAppsController::getCSRFToken());

        $this->assertEquals(
            new AppsPresenter(
                $csrf_presenter,
                new AccountTabPresenterCollection($user, AccountAppsController::URL),
                new AccountAppPresenter(
                    1,
                    'Jenkins',
                    'Public project',
                    new OAuth2ScopeDefinitionPresenter($foobar_scope->getDefinition())
                ),
                new AccountAppPresenter(
                    2,
                    'My Custom REST Consumer',
                    'Private project',
                    new OAuth2ScopeDefinitionPresenter($foobar_scope->getDefinition()),
                    new OAuth2ScopeDefinitionPresenter($typevalue_scope->getDefinition())
                ),
                new AccountAppPresenter(
                    3,
                    'Site level app',
                    null,
                    new OAuth2ScopeDefinitionPresenter($foobar_scope->getDefinition()),
                ),
            ),
            $this->builder->build($user, $csrf_presenter)
        );
    }

    private function buildFooBarScopeDefinition(): AuthenticationScope
    {
        $foobar_scope      = $this->createMock(AuthenticationScope::class);
        $foobar_definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Foo Bar';
            }

            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $foobar_scope->method('getDefinition')->willReturn($foobar_definition);
        return $foobar_scope;
    }

    private function buildTypeValueScopeDefinition(): AuthenticationScope
    {
        $typevalue_scope      = $this->createMock(AuthenticationScope::class);
        $typevalue_definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Type Value';
            }

            public function getDescription(): string
            {
                return 'Other test scope';
            }
        };
        $typevalue_scope->method('getDefinition')->willReturn($typevalue_definition);
        return $typevalue_scope;
    }
}
