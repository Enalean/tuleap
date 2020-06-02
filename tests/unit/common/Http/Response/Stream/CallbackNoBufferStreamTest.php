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
 */

declare(strict_types=1);

namespace Tuleap\Http\Response\Stream;

use PHPUnit\Framework\TestCase;

final class CallbackNoBufferStreamTest extends TestCase
{
    public function testCanOutput(): void
    {
        $stream = new CallbackNoBufferStream(
            static function (): void {
                echo 'Test stream';
            }
        );

        $this->assertFalse($stream->eof());
        echo $stream;
        $this->expectOutputString('Test stream');
        $this->assertTrue($stream->eof());
    }

    public function testStreamDoesNotAnnounceItCanDoIOOperations(): void
    {
        $stream = new CallbackNoBufferStream(
            static function (): void {
            }
        );
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertNull($stream->detach());
        $this->assertNull($stream->getMetadata());
    }
}
