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

namespace Tuleap\User\OAuth2\Scope;

use Luracast\Restler\Data\ApiMethodInfo;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

final class OAuth2ScopeExtractorRESTEndpointTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&AuthenticationScopeBuilder $scope_builder;
    private OAuth2ScopeExtractorRESTEndpoint $extractor;

    protected function setUp(): void
    {
        $this->scope_builder = $this->createMock(AuthenticationScopeBuilder::class);

        $this->extractor = new OAuth2ScopeExtractorRESTEndpoint($this->scope_builder);
    }

    public function testExtractsScopeFromEndpointInformation(): void
    {
        $api_method_info                           = new ApiMethodInfo();
        $scope_identifier_key                      = 'validscopeidentifierkey';
        $api_method_info->metadata['oauth2-scope'] = $scope_identifier_key;

        $expected_scope = $this->createMock(AuthenticationScope::class);
        $this->scope_builder->expects(self::atLeast(1))
            ->method('buildAuthenticationScopeFromScopeIdentifier')
            ->with(self::callback(function (AuthenticationScopeIdentifier $identifier) use ($scope_identifier_key): bool {
                return $identifier->toString() === $scope_identifier_key;
            }))
            ->willReturn($expected_scope);

        $scope = $this->extractor->extractRequiredScope($api_method_info);
        self::assertSame($expected_scope, $scope);
    }

    public function testDoesNotExtractScopeWhenNoneHaveBeenDefinedOnTheEndpoint(): void
    {
        $this->expectException(NoOAuth2ScopeOnRESTEndpointException::class);
        $this->extractor->extractRequiredScope(new ApiMethodInfo());
    }

    public function testFailsTheExtractionWhenTheScopeIdentifierKeyGivenInTheAnnotationIsInvalid(): void
    {
        $api_method_info                           = new ApiMethodInfo();
        $api_method_info->metadata['oauth2-scope'] = '@broken identifier key@';

        $this->expectException(OAuth2ScopeRESTEndpointInvalidException::class);
        $this->extractor->extractRequiredScope($api_method_info);
    }

    public function testFailsTheExtractionWhenTheScopeIdentifierKeyCannotBeMatchedAgainstAnExistingScope(): void
    {
        $api_method_info                           = new ApiMethodInfo();
        $api_method_info->metadata['oauth2-scope'] = 'doesnotexist';

        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(null);

        $this->expectException(OAuth2ScopeRESTEndpointInvalidException::class);
        $this->extractor->extractRequiredScope($api_method_info);
    }
}
