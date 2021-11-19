<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter;

use Psr\Log\LoggerInterface;

final class JiraCollectionBuilder
{
    private const PARAM_START_AT = 'startAt';

    /**
     * @throws UnexpectedFormatException
     * @throws JiraConnectionException
     * @throws \JsonException
     */
    public static function iterateUntilTotal(JiraClient $client, LoggerInterface $logger, string $base_url, string $key): \Generator
    {
        $fetched_items_counter = 0;
        do {
            $url_with_offset = self::addOffsetToUrl($base_url, $fetched_items_counter);
            $logger->info('GET: ' . $url_with_offset);
            $json = $client->getUrl($url_with_offset);
            if (! isset($json[$key], $json['total'])) {
                throw new UnexpectedFormatException(sprintf('%s is supposed to return a payload with `total` and `%s`', $url_with_offset, $key));
            }
            foreach ($json[$key] as $value) {
                yield $value;
                $fetched_items_counter++;
            }
        } while ($json['total'] !== $fetched_items_counter);
    }

    /**
     * @throws UnexpectedFormatException
     * @throws JiraConnectionException
     * @throws \JsonException
     */
    public static function iterateUntilIsLast(JiraClient $client, LoggerInterface $logger, string $base_url, string $key): \Generator
    {
        $start_at = 0;
        do {
            $url_with_offset = self::addOffsetToUrl($base_url, $start_at);
            $logger->info('GET ' . $url_with_offset);
            $json = $client->getUrl($url_with_offset);
            if (! isset($json['isLast'], $json[$key])) {
                throw new UnexpectedFormatException(sprintf('%s route did not return the expected format: `isLast` or `%s` key are missing', $url_with_offset, $key));
            }
            foreach ($json[$key] as $element) {
                yield $element;
                $start_at++;
            }
        } while ($json['isLast'] !== true);
    }

    private static function addOffsetToUrl(string $url, int $start_at): string
    {
        $url_parts = parse_url($url);
        $scheme    = isset($url_parts['scheme']) ? $url_parts['scheme'] . '://' : '';
        $host      = $url_parts['host'] ?? '';
        $port      = isset($url_parts['port']) ? ':' . $url_parts['port'] : '';
        $path      = $url_parts['path'] ?? '';
        $query     = [];
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $query);
        }
        $query[self::PARAM_START_AT] = $start_at;
        return $scheme . $host . $port . $path . '?' . http_build_query($query);
    }
}
