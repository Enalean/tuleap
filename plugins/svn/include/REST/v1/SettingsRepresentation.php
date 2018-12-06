<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class SettingsRepresentation implements SettingsRepresentationInterface
{
    /**
     * @var CommitRulesRepresentation {@type \Tuleap\SVN\REST\v1\CommitRulesRepresentation} {@required false}
     */
    public $commit_rules;

    /**
     * @var ImmutableTagRepresentation {@type \Tuleap\SVN\REST\v1\ImmutableTagRepresentation} {@required false}
     */
    public $immutable_tags;

    /**
     * @var string {@type string} {@required false}
     */
    public $access_file;

    /**
     * @var array {@type \Tuleap\SVN\REST\v1\NotificationRepresentation} {@required false}
     */
    public $email_notifications;

    public function build(
        CommitRulesRepresentation $commit_hook_representation,
        ImmutableTagRepresentation $immutable_tag_representation,
        AccessFileHistory $access_file_history,
        array $email_representation
    ) {
        $this->commit_rules        = $commit_hook_representation;
        $this->immutable_tags      = $immutable_tag_representation;
        $this->access_file         = $access_file_history->getContent();
        $this->email_notifications = $email_representation;
    }
}
