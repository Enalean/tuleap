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

namespace Tuleap\OAuth2Server\OpenIDConnect\Discovery;

use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\language\LanguageTagFormatter;

class ConfigurationResponseRepresentationBuilder
{
    /**
     * @var \BaseLanguageFactory
     */
    private $language_factory;
    /**
     * @var AuthenticationScopeBuilder
     */
    private $scope_builder;

    public function __construct(\BaseLanguageFactory $language_factory, AuthenticationScopeBuilder $scope_builder)
    {
        $this->language_factory = $language_factory;
        $this->scope_builder    = $scope_builder;
    }

    /**
     * @return string[]
     */
    private function getSupportedLanguages(): array
    {
        $rfc5646_formatted_language_tags = [];
        foreach ($this->language_factory->getAvailableLanguages() as $locale => $label) {
            $rfc5646_formatted_language_tags[] = LanguageTagFormatter::formatAsRFC5646LanguageTag($locale);
        }
        return $rfc5646_formatted_language_tags;
    }

    /**
     * @return string[]
     */
    private function getSupportedScopeIdentifiers(): array
    {
        $scope_identifiers = [];
        foreach ($this->scope_builder->buildAllAvailableAuthenticationScopes() as $scope) {
            $scope_identifiers[] = $scope->getIdentifier()->toString();
        }
        return $scope_identifiers;
    }

    public function build(): ConfigurationResponseRepresentation
    {
        return new ConfigurationResponseRepresentation(
            $this->getSupportedScopeIdentifiers(),
            $this->getSupportedLanguages()
        );
    }
}
