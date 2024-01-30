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

use Tuleap\SVN\AccessControl\AccessFileHistory;

/**
 * @psalm-immutable
 */
class SettingsGETRepresentation
{
    /**
     * @var CommitRulesRepresentation commit rules representation {@type \Tuleap\SVN\REST\v1\CommitRulesRepresentation} {@required false}
     */
    public $commit_rules;

    /**
     * @var ImmutableTagRepresentation immutable tags representations {@type \Tuleap\SVN\REST\v1\ImmutableTagRepresentation} {@required false}
     */
    public $immutable_tags;

    /**
     * @var string access file content {@required false}
     */
    public $access_file;

    /**
     * @var NotificationGETRepresentation[] notifications representation {@type \Tuleap\SVN\REST\v1\NotificationGETRepresentation} {@required false}
     */
    public $email_notifications;

    /**
     * @var bool If true, Tuleap generates default permissions for [/] according to project visibility {@required false}
     */
    public $has_default_permissions;

    /**
     * @param NotificationGETRepresentation[] $email_notifications
     */
    private function __construct(
        CommitRulesRepresentation $commit_rules,
        ImmutableTagRepresentation $immutable_tags,
        string $access_file,
        array $email_notifications,
        bool $has_default_permissions,
    ) {
        $this->commit_rules            = $commit_rules;
        $this->immutable_tags          = $immutable_tags;
        $this->access_file             = $access_file;
        $this->email_notifications     = $email_notifications;
        $this->has_default_permissions = $has_default_permissions;
    }

    public static function build(
        CommitRulesRepresentation $commit_hook_representation,
        ImmutableTagRepresentation $immutable_tag_representation,
        AccessFileHistory $access_file_history,
        array $email_representation,
        bool $has_default_permissions,
    ): self {
        return new self(
            $commit_hook_representation,
            $immutable_tag_representation,
            $access_file_history->getContent(),
            $email_representation,
            $has_default_permissions,
        );
    }
}
