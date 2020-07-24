<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use Tuleap\REST\I18NRestException;
use Tuleap\REST\JsonDecoder;

class UserGroupQueryParameterParser
{
    private const WITH_SYSTEM_USER_GROUPS_PARAMETER = 'with_system_user_groups';

    private $json_decoder;

    public function __construct(JsonDecoder $json_decoder)
    {
        $this->json_decoder = $json_decoder;
    }

    /**
     * @throws I18NRestException
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     */
    public function parse(string $query): UserGroupQueryRepresentation
    {
        if ($query === '') {
            return UserGroupQueryRepresentation::build(false);
        }

        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query[self::WITH_SYSTEM_USER_GROUPS_PARAMETER])) {
            throw new I18NRestException(400, sprintf(
                dgettext('tuleap-core', 'parameter "query" syntax error: "%s" property not found'),
                self::WITH_SYSTEM_USER_GROUPS_PARAMETER
            ));
        }

        if (! is_bool($json_query[self::WITH_SYSTEM_USER_GROUPS_PARAMETER])) {
            throw new I18NRestException(400, sprintf(
                dgettext('tuleap-core', 'parameter "query", property "%s" invalid type: boolean expected'),
                self::WITH_SYSTEM_USER_GROUPS_PARAMETER
            ));
        }
        return UserGroupQueryRepresentation::build($json_query[self::WITH_SYSTEM_USER_GROUPS_PARAMETER] === true);
    }
}
