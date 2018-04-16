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
    private $password_configuration;
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

    public static function build()
    {
        return new self(
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO()),
            $GLOBALS['Language']
        );
    }

    public function check($password)
    {
        $password_strategy = new PasswordStrategy($this->password_configuration);
        include($this->language->getContent('account/password_strategy'));
        $valid  = $password_strategy->validate($password);
        $this->errors = $password_strategy->errors;
        return $valid;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
