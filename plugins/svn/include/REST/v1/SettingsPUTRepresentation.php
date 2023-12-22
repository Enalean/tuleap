<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

/**
 * @psalm-immutable
 */
class SettingsPUTRepresentation
{
    /**
     * @var CommitRulesRepresentation commit rules representations {@type \Tuleap\SVN\REST\v1\CommitRulesRepresentation} {@required true}
     */
    public $commit_rules;

    /**
     * @var ImmutableTagRepresentation immutable tags representations {@type \Tuleap\SVN\REST\v1\ImmutableTagRepresentation} {@required true}
     */
    public $immutable_tags;

    /**
     * @var NotificationPOSTPUTRepresentation[] notifications representations {@type \Tuleap\SVN\REST\v1\NotificationPOSTPUTRepresentation} {@required true}
     */
    public $email_notifications;

    /**
     * @var string access file content {@required false}
     */
    public $access_file;
}
