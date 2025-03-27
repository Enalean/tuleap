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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Tuleap\OAuth2Server\User\AuthorizationComparator;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2ConsentCheckerTest extends TestCase
{
    private OAuth2ConsentChecker $consent_checker;

    /**
     * @var AuthorizationComparator&\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorization_comparator;

    protected function setUp(): void
    {
        $this->authorization_comparator = $this->createMock(AuthorizationComparator::class);

        $this->consent_checker = new OAuth2ConsentChecker(
            $this->authorization_comparator,
            OAuth2OfflineAccessScope::fromItself(),
        );
    }

    public function testItReturnsTrueIfConsentIsRequiredInPromptValues(): void
    {
        $prompt_values = ['consent'];
        $user          = UserTestBuilder::aUser()->build();
        $project       = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $client_app    = new OAuth2App(
            1,
            'Jenkins',
            'https://example.com/redirect',
            true,
            $project
        );
        $scopes        = [OAuth2OfflineAccessScope::fromItself()];

        self::assertTrue(
            $this->consent_checker->isConsentRequired(
                $prompt_values,
                $user,
                $client_app,
                $scopes
            )
        );
    }

    public function testItReturnsTrueIfNoPromptValuesAndRequestedScopesNotAlreadyGranted(): void
    {
        $prompt_values = [];
        $user          = UserTestBuilder::aUser()->build();
        $project       = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $client_app    = new OAuth2App(
            1,
            'Jenkins',
            'https://example.com/redirect',
            true,
            $project
        );
        $scopes        = [OAuth2OfflineAccessScope::fromItself()];

        $this->authorization_comparator
            ->expects($this->once())
            ->method('areRequestedScopesAlreadyGranted')
            ->willReturn(false);

        self::assertTrue(
            $this->consent_checker->isConsentRequired(
                $prompt_values,
                $user,
                $client_app,
                $scopes
            )
        );
    }

    public function testItReturnsTrueIfScopeCoversOfflineAccess(): void
    {
        $prompt_values = [];
        $user          = UserTestBuilder::aUser()->build();
        $project       = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $client_app    = new OAuth2App(
            1,
            'Jenkins',
            'https://example.com/redirect',
            true,
            $project
        );
        $scopes        = [OAuth2OfflineAccessScope::fromItself()];

        $this->authorization_comparator
            ->expects($this->once())
            ->method('areRequestedScopesAlreadyGranted')
            ->willReturn(true);

        self::assertTrue(
            $this->consent_checker->isConsentRequired(
                $prompt_values,
                $user,
                $client_app,
                $scopes
            )
        );
    }

    public function testItReturnsFalseInAllOtherCases(): void
    {
        $prompt_values = [];
        $user          = UserTestBuilder::aUser()->build();
        $project       = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $client_app    = new OAuth2App(
            1,
            'Jenkins',
            'https://example.com/redirect',
            true,
            $project
        );
        $scopes        = [OAuth2SignInScope::fromItself()];

        $this->authorization_comparator
            ->expects($this->once())
            ->method('areRequestedScopesAlreadyGranted')
            ->willReturn(true);

        self::assertFalse(
            $this->consent_checker->isConsentRequired(
                $prompt_values,
                $user,
                $client_app,
                $scopes
            )
        );
    }
}
