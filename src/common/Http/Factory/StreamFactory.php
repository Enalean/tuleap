<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Http\Factory;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @psalm-internal \Tuleap\Http
 * @internal Call Tuleap\Http\HTTPFactoryBuilder::streamFactory instead
 */
final class StreamFactory implements StreamFactoryInterface
{
    private const PHP_INPUT_WRAPPER = 'php://input';

    private StreamFactoryInterface $stream_factory;

    public function __construct(StreamFactoryInterface $stream_factory)
    {
        $this->stream_factory = $stream_factory;
    }

    #[\Override]
    public function createStream(string $content = ''): StreamInterface
    {
        return $this->stream_factory->createStream($content);
    }

    #[\Override]
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if ($filename === self::PHP_INPUT_WRAPPER) {
            return $this->createStreamFromResource(\GuzzleHttp\Psr7\Utils::tryFopen($filename, $mode));
        }
        return $this->stream_factory->createStreamFromFile($filename, $mode);
    }

    #[\Override]
    public function createStreamFromResource($resource): StreamInterface
    {
        /*
         * The base implementation we rely on (guzzle/psr7) attempts to buffer php://input into
         * php://temp which can lead to a large memory consumption.
         * They do that to avoid some issues when ext-curl is not installed. Since this cannot happen
         * in our situation we can safely ignore the issue and directly use the stream
         */
        if ((\stream_get_meta_data($resource)['uri'] ?? '') === self::PHP_INPUT_WRAPPER) {
            return new \GuzzleHttp\Psr7\Stream($resource);
        }
        return $this->stream_factory->createStreamFromResource($resource);
    }
}
