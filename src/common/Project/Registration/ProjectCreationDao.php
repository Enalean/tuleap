<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration;

use ForgeConfig;
use Project;
use Tuleap\DB\DataAccessObject;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectCreationData;

final class ProjectCreationDao extends DataAccessObject implements StoreProjectInformation
{
    public const TYPE_PROJECT   = 1;
    private const TYPE_TEMPLATE = 2;
    private const TYPE_TEST     = 3;

    public function create(ProjectCreationData $data): int
    {
        if (ForgeConfig::get('sys_disable_subdomains')) {
            $http_domain = \Tuleap\ServerHostname::hostnameWithHTTPSPort();
        } else {
            $http_domain = $data->getUnixName() . '.' . \Tuleap\ServerHostname::hostnameWithHTTPSPort();
        }

        $access = $data->getAccess();

        $type = self::TYPE_PROJECT;
        if ($data->isTest()) {
            $type = self::TYPE_TEST;
        } elseif ($data->isTemplate()) {
            $type = self::TYPE_TEMPLATE;
        }

        return (int) $this->getDB()->insertReturnId(
            'groups',
            [
                'group_name'          => $data->getFullName(),
                'access'              => $access,
                'unix_group_name'     => $data->getUnixName(),
                'http_domain'         => $http_domain,
                'status'              => Project::STATUS_PENDING,
                'short_description'   => $data->getShortDescription(),
                'register_time'       => time(),
                'rand_hash'           => bin2hex(random_bytes(16)),
                'built_from_template' => $data->getBuiltFromTemplateProject()->getProject()->getID(),
                'type'                => $type,
                'icon_codepoint'      => EmojiCodepointConverter::convertEmojiToStoreFormat($data->getIconCodePoint()),
            ]
        );
    }
}
