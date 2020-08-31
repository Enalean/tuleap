<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

/**
 * Check if an email address is valid or not in Codendi context.
 *
 * This rule is influenced by a global variable 'sys_disable_subdomain'. If
 * this variable is set (no subdomain for codendi) and only in this case, emails
 * like 'user@codendi' are allowed.
 *
 * The faulty email address is available with $this->getErrorMessage();
 */
class Rule_Email extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $separator;
    public function __construct($separator = \null)
    {
        parent::__construct();
        $this->separator = $separator;
    }
    public function isValid($val): bool
    {
        if ($this->separator !== \null) {
            // If separator is defined, split the string and check each email.
            $emails = \preg_split('/' . $this->separator . '/D', $val);
            foreach ($emails as $email) {
                if (! $this->validEmail(\trim(\rtrim($email)))) {
                    return \false;
                }
            }
            return \true;
        }
        // $val must contains only one email address
        return (bool) $this->validEmail($val);
    }
    /**
     * Check email validity
     *
     * Important note: this is very important to keep the 'D' regexp modifier
     * as this is the only way not to be bothered by injections of \n into the
     * email address.
     *
     * Spaces are allowed at the beginning and the end of the address.
     */
    public function validEmail($email)
    {
        $valid_chars = '-!#$%&\'*+0-9=?A-Z^_`a-z{|}~\.';
        if (\ForgeConfig::exists('sys_disable_subdomains') && \ForgeConfig::get('sys_disable_subdomains')) {
            $valid_domain = '[' . $valid_chars . ']+';
        } else {
            $valid_domain = '[' . $valid_chars . ']+\.[' . $valid_chars . ']+';
        }
        $regexp = '/^[' . $valid_chars . ']+' . '@' . $valid_domain . '$/D';
        return \preg_match($regexp, $email);
    }
}
