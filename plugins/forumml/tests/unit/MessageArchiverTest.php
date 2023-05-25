<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML;

use Tuleap\ForumML\Incoming\IncomingAttachment;
use Tuleap\ForumML\Incoming\IncomingMail;

final class MessageArchiverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEmailBodyIsStored(): void
    {
        $archiver = $this->createPartialMock(MessageArchiver::class, ['insertMessage', 'insertAttachment']);
        $archiver->expects(self::once())->method('insertMessage')->willReturn(1);
        $archiver->expects(self::never())->method('insertAttachment');

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getHeaders')->willReturn([]);
        $incoming_mail->method('getAttachments')->willReturn([]);
        $storage = $this->createMock(\ForumML_FileStorage::class);

        $archiver->storeEmail($incoming_mail, $storage);
    }

    public function testAttachmentsAreSaved(): void
    {
        $archiver = $this->createPartialMock(MessageArchiver::class, ['insertMessage', 'insertAttachment']);
        $archiver->expects(self::once())->method('insertMessage')->willReturn(1);
        $archiver->expects(self::exactly(3))->method('insertAttachment');

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getHeaders')->willReturn([]);
        $attachment_1 = $this->getAttachmentMockExpectingToBeStored();
        $attachment_2 = $this->getAttachmentMockExpectingToBeStored();
        $attachment_3 = $this->getAttachmentMockExpectingToBeStored();
        $incoming_mail->method('getAttachments')->willReturn([$attachment_1, $attachment_2, $attachment_3]);
        $storage = $this->createMock(\ForumML_FileStorage::class);
        $storage->expects(self::exactly(3))->method('store');

        $archiver->storeEmail($incoming_mail, $storage);
    }

    private function getAttachmentMockExpectingToBeStored(): IncomingAttachment
    {
        $attachment = $this->createMock(IncomingAttachment::class);
        $attachment->expects(self::atLeast(1))->method('getFilename');
        $attachment->expects(self::atLeast(1))->method('getContent');
        $attachment->expects(self::atLeast(1))->method('getContentType');
        $attachment->expects(self::atLeast(1))->method('getContentID');
        return $attachment;
    }
}
