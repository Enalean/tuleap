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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;

final class NotificationsPresenter
{
    /**
     * @var bool
     * @psalm-readonly
     */
    public $site_email_updates_checked = false;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $site_email_community_checked = false;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $email_format_html;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $email_format_text;

    /**
     * @psalm-readonly
     */
    public bool $has_notifications_on_own_actions;

    public function __construct(
        /**
         * @readonly
         */
        public CSRFSynchronizerToken $csrf_token,
        /**
         * @readonly
         */
        public AccountTabPresenterCollection $tabs,
        /**
         * @readonly
         */
        public NotificationsOnOwnActionsCollection $notification_on_own_actions,
        \PFUser $user,
        string $mail_format_preference,
    ) {
        if ($user->getMailSiteUpdates()) {
            $this->site_email_updates_checked = true;
        }
        if ($user->getMailVA()) {
            $this->site_email_community_checked = true;
        }
        if ($mail_format_preference === Codendi_Mail_Interface::FORMAT_HTML) {
            $this->email_format_html = true;
            $this->email_format_text = false;
        } else {
            $this->email_format_html = false;
            $this->email_format_text = true;
        }
        $this->has_notifications_on_own_actions = count($this->notification_on_own_actions) > 0;
    }
}
