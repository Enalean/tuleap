<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Notification;

use Tracker_Artifact_Changeset;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\SendMailStub;

final class SendMessagesForAdminsTest extends TestCase
{
    private SendMailStub $mail_sender;
    private SendMessagesForAdmins $send_messages_for_admins;
    private Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $this->mail_sender              = SendMailStub::build();
        $this->send_messages_for_admins = new SendMessagesForAdmins($this->mail_sender);
        $this->changeset                = ChangesetTestBuilder::aChangeset('1')->build();
    }

    public function testItSendNothingIfNoMessage(): void
    {
        $this->send_messages_for_admins->sendMessages([], $this->changeset);
        self::assertEquals(0, $this->mail_sender->getCallCounter());
    }

    public function testItSendAsManyMailsAsMessages(): void
    {
        $this->send_messages_for_admins->sendMessages([$this->getMessage(), $this->getMessage(), $this->getMessage()], $this->changeset);
        self::assertEquals(3, $this->mail_sender->getCallCounter());
    }

    private function getMessage(): MessageRepresentation
    {
        return new MessageRepresentation(
            [],
            [],
            '',
            '',
            '',
            '',
            [],
        );
    }
}
