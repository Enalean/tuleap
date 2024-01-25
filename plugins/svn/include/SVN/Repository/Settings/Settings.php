<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository\Settings;

use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\MailNotification;

/**
 * @psalm-immutable
 */
final class Settings
{
    /**
     * @var MailNotification[]
     */
    private readonly array $mail_notification;
    /**
     * @var AccessFileHistory[]
     */
    private readonly array $access_file_history;

    public function __construct(
        private readonly array $commit_rules,
        private readonly ImmutableTag $immutable_tag,
        private readonly string $access_file,
        array $mail_notification,
        array $access_file_history,
        private readonly int $used_version,
        private readonly bool $is_access_file_already_purged,
        public readonly bool $has_default_permissions,
    ) {
        $this->mail_notification   = $mail_notification;
        $this->access_file_history = $access_file_history;
    }

    public function getCommitRules(): array
    {
        return $this->commit_rules;
    }

    public function getImmutableTag(): ImmutableTag
    {
        return $this->immutable_tag;
    }

    public function getAccessFileContent(): string
    {
        return $this->access_file;
    }

    /**
     * @return MailNotification[]
     */
    public function getMailNotification(): array
    {
        return $this->mail_notification;
    }

    public function hasSettings(): bool
    {
        return ! empty($this->commit_rules)
            || ! empty($this->immutable_tag->getPaths())
            || ! empty($this->immutable_tag->getWhitelist())
            || ! empty($this->mail_notification)
            || $this->access_file !== "";
    }

    /**
     * @return AccessFileHistory[]
     */
    public function getAccessFileHistory(): array
    {
        return $this->access_file_history;
    }

    public function getUsedVersion(): int
    {
        return $this->used_version;
    }

    public function isAccessFileAlreadyPurged(): bool
    {
        return $this->is_access_file_already_purged;
    }
}
