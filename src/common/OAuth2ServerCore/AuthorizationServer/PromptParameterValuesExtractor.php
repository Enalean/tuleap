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

namespace Tuleap\OAuth2ServerCore\AuthorizationServer;

final class PromptParameterValuesExtractor
{
    // See https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
    public const string PROMPT_NONE           = 'none';
    public const string PROMPT_LOGIN          = 'login';
    public const string PROMPT_CONSENT        = 'consent';
    public const string PROMPT_SELECT_ACCOUNT = 'select_account';

    private const array SUPPORTED_PROMPT_VALUES = [self::PROMPT_NONE, self::PROMPT_LOGIN, self::PROMPT_CONSENT, self::PROMPT_SELECT_ACCOUNT];

    /**
     * @return string[]
     *
     * @psalm-return array<value-of<self::SUPPORTED_PROMPT_VALUES>>
     *
     * @throws PromptNoneParameterCannotBeMixedWithOtherPromptParametersException
     */
    public function extractPromptValues(string $prompt_parameter): array
    {
        $values = [];

        foreach (array_unique(explode(' ', $prompt_parameter)) as $prompt_value) {
            if (in_array($prompt_value, self::SUPPORTED_PROMPT_VALUES, true)) {
                $values[] = $prompt_value;
            }
        }

        if (count($values) > 1 && in_array(self::PROMPT_NONE, $values, true)) {
            throw new PromptNoneParameterCannotBeMixedWithOtherPromptParametersException($values);
        }

        return $values;
    }
}
