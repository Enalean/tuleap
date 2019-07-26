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
 */

namespace Tuleap\Password\HaveIBeenPwned;

class PwnedPasswordChecker
{
    public const PREFIX_SIZE                                  = 5;
    public const NUMBER_OF_OCCURRENCE_TO_CONSIDER_COMPROMISED = 10;

    /**
     * @var PwnedPasswordRangeRetriever
     */
    private $pwned_password_range_retriever;

    public function __construct(PwnedPasswordRangeRetriever $pwned_password_range_retriever)
    {
        $this->pwned_password_range_retriever = $pwned_password_range_retriever;
    }

    /**
     * @return bool
     */
    public function isPasswordCompromised($password)
    {
        $sha1_password        = strtoupper(sha1($password));
        $sha1_password_prefix = substr($sha1_password, 0, self::PREFIX_SIZE);
        $sha1_password_suffix = substr($sha1_password, self::PREFIX_SIZE);

        $hash_suffixes = $this->pwned_password_range_retriever->getHashSuffixesMatchingPrefix($sha1_password_prefix);
        $has_match     = preg_match('/^' . preg_quote($sha1_password_suffix, '/') . '\:(\d+)/m', $hash_suffixes, $matches);

        if ($has_match !== 1) {
            return false;
        }

        return (int) $matches[1] >= self::NUMBER_OF_OCCURRENCE_TO_CONSIDER_COMPROMISED;
    }
}
