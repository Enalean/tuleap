<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForumML\Incoming\IncomingAttachment;
use Tuleap\ForumML\Incoming\IncomingMail;

class MessageArchiverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEmailBodyIsStored()
    {
        $archiver = \Mockery::mock(MessageArchiver::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $archiver->shouldReceive('insertMessage')->once()->andReturns(1);
        $archiver->shouldReceive('insertAttachment')->never();

        $incoming_mail = \Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getHeaders')->andReturns([]);
        $incoming_mail->shouldReceive('getAttachments')->andReturns([]);
        $storage       = \Mockery::mock(\ForumML_FileStorage::class);

        $archiver->storeEmail($incoming_mail, $storage);
    }

    public function testAttachmentsAreSaved()
    {
        $archiver = \Mockery::mock(MessageArchiver::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $archiver->shouldReceive('insertMessage')->once()->andReturns(1);
        $archiver->shouldReceive('insertAttachment')->times(3);

        $incoming_mail = \Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getHeaders')->andReturns([]);
        $attachment_1 = $this->getAttachmentMockExpectingToBeStored();
        $attachment_2 = $this->getAttachmentMockExpectingToBeStored();
        $attachment_3 = $this->getAttachmentMockExpectingToBeStored();
        $incoming_mail->shouldReceive('getAttachments')->andReturns([$attachment_1, $attachment_2, $attachment_3]);
        $storage = \Mockery::mock(\ForumML_FileStorage::class);
        $storage->shouldReceive('store')->times(3);

        $archiver->storeEmail($incoming_mail, $storage);
    }

    private function getAttachmentMockExpectingToBeStored()
    {
        $attachment = \Mockery::mock(IncomingAttachment::class);
        $attachment->shouldReceive('getFilename')->atLeast(1);
        $attachment->shouldReceive('getContent')->atLeast(1);
        $attachment->shouldReceive('getContentType')->atLeast(1);
        $attachment->shouldReceive('getContentID')->atLeast(1);
        return $attachment;
    }
}
