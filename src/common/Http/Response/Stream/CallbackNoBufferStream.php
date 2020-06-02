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

use Psr\Http\Message\StreamInterface;

/**
 * This implementation of a StreamInterface should be used as a last resort.
 * For most scenarios the streams provided by an implementation of \Psr\Http\Message\StreamFactoryInterface should be
 * used.
 *
 * This implementation only exists to deal with cases where you need to output a large body (i.e. something that does
 * not fit in memory) and a limited control on how this body is built.
 *
 * This approach is somewhat tolerated by the PSR-7 [0] but is, by nature, leaky so expect it to potentially break
 * middleware or emitter implementations.
 *
 * [0] https://www.php-fig.org/psr/psr-7/meta/#what-if-i-want-to-directly-emit-output
 */
final class CallbackNoBufferStream implements StreamInterface
{
    /**
     * @var callable
     */
    private $callback;
    /**
     * @var bool
     */
    private $has_been_called = false;

    /**
     * @psalm-param callable():void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell()
    {
        $this->throwExceptionOnDirectIOOperations();
    }

    public function eof(): bool
    {
        return $this->has_been_called;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->throwExceptionOnDirectIOOperations();
    }

    public function rewind(): void
    {
        $this->throwExceptionOnDirectIOOperations();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string)
    {
        $this->throwExceptionOnDirectIOOperations();
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function read($length): string
    {
        $this->throwExceptionOnDirectIOOperations();
    }

    public function getContents(): string
    {
        $this->has_been_called = true;
        ($this->callback)();
        return '';
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    /**
     * @psalm-return never-return
     */
    private function throwExceptionOnDirectIOOperations(): void
    {
        throw new \RuntimeException("Direct I/O operations are not accepted by this stream interface implementation");
    }
}
