<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\MailNotification;

class Settings
{
    /**
     * @var array
     */
    private $commit_rules;

    /**
     * @var ImmutableTag
     */
    private $immutable_tag;

    /**
     * @var string
     */
    private $access_file;
    /**
     * @var MailNotification[]
     */
    private $mail_notification;
    /**
     * @var AccessFileHistory[]
     */
    private $access_file_history;
    /**
     * @var int
     */
    private $used_version;
    /**
     * @var bool
     */
    private $is_access_file_already_purged;

    public function __construct(
        array $commit_rules,
        ImmutableTag $immutable_tag,
        $access_file,
        array $mail_notification,
        array $access_file_history,
        $used_version,
        $is_access_file_already_purged
    ) {
        $this->commit_rules                  = $commit_rules;
        $this->immutable_tag                 = $immutable_tag;
        $this->access_file                   = $access_file;
        $this->mail_notification             = $mail_notification;
        $this->access_file_history           = $access_file_history;
        $this->used_version                  = $used_version;
        $this->is_access_file_already_purged = $is_access_file_already_purged;
    }

    /**
     * @return array|null
     */
    public function getCommitRules()
    {
        return $this->commit_rules;
    }

    /**
     * @return ImmutableTag|null
     */
    public function getImmutableTag()
    {
        return $this->immutable_tag;
    }

    /**
     * @return string|null
     */
    public function getAccessFileContent()
    {
        return $this->access_file;
    }

    /**
     * @return MailNotification[]|null
     */
    public function getMailNotification()
    {
        return $this->mail_notification;
    }

    public function hasSettings()
    {
        return count($this->commit_rules) > 0
            || count($this->immutable_tag->getPaths()) > 0
            || count($this->immutable_tag->getWhitelist()) > 0
            || count($this->mail_notification) > 0
            || $this->access_file !== "";
    }

    /**
     * @return AccessFileHistory[]
     */
    public function getAccessFileHistory(): array
    {
        return $this->access_file_history;
    }

    public function getUsedVersion()
    {
        return $this->used_version;
    }

    /**
     * @return bool
     */
    public function isAccessFileAlreadyPurged()
    {
        return $this->is_access_file_already_purged;
    }
}
