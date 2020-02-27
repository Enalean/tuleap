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
 *
 */

namespace Tuleap\Password;

use PasswordStrategy;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;

class PasswordSanityChecker
{
    /**
     * @var Configuration\PasswordConfiguration
     */
    private $password_configuration;
    /**
     * @psalm-var list<string>
     * @var array
     */
    private $errors = [];
    /**
     * @var \BaseLanguage
     */
    private $language;

    public function __construct(PasswordConfigurationRetriever $retriever, \BaseLanguage $language)
    {
        $this->password_configuration = $retriever->getPasswordConfiguration();
        $this->language               = $language;
    }

    public static function build(): self
    {
        return new self(
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO()),
            $GLOBALS['Language']
        );
    }

    public function check(string $password): bool
    {
        $password_strategy = $this->getStrategy();
        $valid  = $password_strategy->validate($password);
        $this->errors = $password_strategy->errors;
        return $valid;
    }

    /**
     * @psalm-return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return \PasswordValidator[]
     */
    public function getValidators(): array
    {
        return $this->getStrategy()->validators;
    }

    private function getStrategy(): PasswordStrategy
    {
        $password_strategy = new PasswordStrategy($this->password_configuration);
        include($this->language->getContent('account/password_strategy'));
        return $password_strategy;
    }
}
