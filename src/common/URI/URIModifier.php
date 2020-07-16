<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\URI;

class URIModifier
{
    /**
     * @see RFC3986 section 5.2.4 https://tools.ietf.org/html/rfc3986#section-5.2.4
     *
     * @return string
     *
     * @psalm-pure
     */
    public static function removeDotSegments($uri)
    {
        if (strpos($uri, '.') === false) {
            return $uri;
        }

        $input_buffer    = explode(DIRECTORY_SEPARATOR, $uri);
        $filtered_buffer = array_reduce($input_buffer, function (array $carry, $path_segment) {
            if ($path_segment === '..') {
                array_pop($carry);

                return $carry;
            }

            if ($path_segment !== '.') {
                $carry[] = $path_segment;
            }

            return $carry;
        }, []);

        $filtered_uri = implode(DIRECTORY_SEPARATOR, $filtered_buffer);

        $last_element_input_buffer = end($input_buffer);
        if ($last_element_input_buffer === '.' || $last_element_input_buffer === '..') {
            $filtered_uri .= DIRECTORY_SEPARATOR;
        }

        return $filtered_uri;
    }

    /**
     * @see RFC3986 section 6.2.2.2 https://tools.ietf.org/html/rfc3986#section-6.2.2.2
     *
     * @return string
     *
     * @psalm-pure
     */
    public static function normalizePercentEncoding($uri)
    {
        $uri_parts = explode(DIRECTORY_SEPARATOR, $uri);

        foreach ($uri_parts as &$uri_part) {
            $uri_part = rawurlencode($uri_part);
        }

        return implode(DIRECTORY_SEPARATOR, $uri_parts);
    }

    /**
     * @return string
     *
     * @psalm-pure
     */
    public static function removeEmptySegments($uri)
    {
        $is_vfs_stream_path = strpos($uri, 'vfs://') === 0;

        $new_uri = preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '+/', DIRECTORY_SEPARATOR, $uri);

        if ($is_vfs_stream_path) {
            $new_uri = preg_replace('%^vfs:%', 'vfs:/', $new_uri);
        }

        return $new_uri;
    }
}
