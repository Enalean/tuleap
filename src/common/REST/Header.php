<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use DateTime;
use DateTimeZone;
use Tuleap\ServerHostname;

class Header
{
    public const GET     = 'GET';
    public const OPTIONS = 'OPTIONS';
    public const PUT     = 'PUT';
    public const POST    = 'POST';
    public const DELETE  = 'DELETE';
    public const PATCH   = 'PATCH';

    public const CORS_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const ALLOW              = 'Allow';
    public const LAST_MODIFIED      = 'Last-Modified';
    public const ETAG               = 'Etag';
    public const LOCATION           = 'Location';

    public const X_PAGINATION_LIMIT     = 'X-PAGINATION-LIMIT';
    public const X_PAGINATION_OFFSET    = 'X-PAGINATION-OFFSET';
    public const X_PAGINATION_SIZE      = 'X-PAGINATION-SIZE';
    public const X_PAGINATION_LIMIT_MAX = 'X-PAGINATION-LIMIT-MAX';

    public const X_QUOTA                     = 'X-QUOTA';
    public const X_DISK_USAGE                = 'X-DISK-USAGE';
    public const X_UPLOAD_MAX_FILE_CHUNKSIZE = 'X-UPLOAD-MAX-FILE-CHUNKSIZE';

    public const X_RATELIMIT_REMAINING = 'X-RateLimit-Remaining';
    public const X_RATELIMIT_LIMIT     = 'X-RateLimit-Limit';

    public const RFC1123 = 'D, d M Y H:i:s \G\M\T';

    /**
     * Sends headers in RFC1123 compliant format
     * See https://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
     *
     * Be careful, if you don't specify the timezone, despite usage of RFC1123
     * const, the resulting string won't ends with GMT and this might be a
     * problem with clients or proxy that follow RFC very strictly.
     *
     * @param int $timestamp
     */
    public static function lastModified($timestamp)
    {
        $time = new DateTime();
        $time->setTimestamp($timestamp);
        $time->setTimezone(new DateTimeZone('GMT'));
        self::sendHeader(self::LAST_MODIFIED, $time->format(self::RFC1123));
    }

    public static function ETag($hash)
    {
        self::sendHeader(self::ETAG, $hash);
    }

    public static function Location($uri)
    {
        $route = ServerHostname::HTTPSUrl() . $uri;

        self::sendHeader(self::LOCATION, $route);
    }

    public static function allowOptions()
    {
        self::sendAllowHeaders([self::OPTIONS]);
    }

    public static function allowOptionsGet()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET]);
    }

    public static function allowOptionsPostDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::POST, self::DELETE]);
    }

    public static function allowOptionsDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::DELETE]);
    }

    public static function allowOptionsGetPut()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT]);
    }

    public static function allowOptionsGetPutPost()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::POST]);
    }

    public static function allowOptionsGetPutPostDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::POST, self::DELETE]);
    }

    public static function allowOptionsGetPutPostPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::POST, self::PATCH]);
    }

    public static function allowOptionsGetPutPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::PATCH]);
    }

    public static function allowOptionsGetPutDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::DELETE]);
    }

    public static function allowOptionsGetPutDeletePatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PUT, self::DELETE, self::PATCH]);
    }

    public static function allowOptionsPut()
    {
        self::sendAllowHeaders([self::OPTIONS, self::PUT]);
    }

    public static function allowOptionsPost()
    {
        self::sendAllowHeaders([self::OPTIONS, self::POST]);
    }

    public static function allowOptionsPostPut()
    {
        self::sendAllowHeaders([self::OPTIONS, self::POST, self::PUT]);
    }

    public static function allowOptionsGetPost()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::POST]);
    }

    public static function allowOptionsGetPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PATCH]);
    }

    public static function allowOptionsGetPatchDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::PATCH, self::DELETE]);
    }

    public static function allowOptionsPatchDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::PATCH, self::DELETE]);
    }

    public static function allowOptionsGetDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::DELETE]);
    }

    public static function allowOptionsGetPostDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::POST, self::DELETE]);
    }

    public static function allowOptionsPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::PATCH]);
    }

    public static function allowOptionsPostPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::POST, self::PATCH]);
    }

    public static function allowOptionsGetPostPatch()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::POST, self::PATCH]);
    }

    public static function allowOptionsGetPostPatchDelete()
    {
        self::sendAllowHeaders([self::OPTIONS, self::GET, self::POST, self::PATCH, self::DELETE]);
    }

    private static function sendAllowHeaders($methods)
    {
        $methods = implode(', ', $methods);
        self::sendHeader(self::ALLOW, $methods);
        self::sendHeader(self::CORS_ALLOW_METHODS, $methods);
    }

    public static function sendPaginationHeaders(int $limit, int $offset, int $size, int $max_limit): void
    {
        self::sendHeader(self::X_PAGINATION_LIMIT, self::getValidValueForPaginationHeader($limit));
        self::sendHeader(self::X_PAGINATION_OFFSET, self::getValidValueForPaginationHeader($offset));
        self::sendHeader(self::X_PAGINATION_SIZE, self::getValidValueForPaginationHeader($size));
        self::sendHeader(self::X_PAGINATION_LIMIT_MAX, self::getValidValueForPaginationHeader($max_limit));
    }

    /**
     * @psalm-pure
     *
     * @psalm-taint-escape header
     */
    private static function getValidValueForPaginationHeader(int $value): int
    {
        if ($value < 0) {
            return 0;
        }
        return $value;
    }

    public static function sendOptionsPaginationHeaders($limit, $offset, $max_limit)
    {
        self::sendHeader(self::X_PAGINATION_LIMIT, $limit);
        self::sendHeader(self::X_PAGINATION_OFFSET, $offset);
        self::sendHeader(self::X_PAGINATION_LIMIT_MAX, $max_limit);
    }

    public static function sendMaxFileChunkSizeHeaders($size)
    {
        self::sendHeader(self::X_UPLOAD_MAX_FILE_CHUNKSIZE, $size);
    }

    public static function sendRateLimitHeaders($rate_limit, $remaining_calls)
    {
        self::sendHeader(self::X_RATELIMIT_LIMIT, $rate_limit);
        self::sendHeader(self::X_RATELIMIT_REMAINING, $remaining_calls);
    }

    public static function sendQuotaHeader($quota)
    {
        self::sendHeader(self::X_QUOTA, $quota);
    }

    public static function sendDiskUsage($disk_usage)
    {
        self::sendHeader(self::X_DISK_USAGE, $disk_usage);
    }

    private static function sendHeader($name, $value)
    {
        header($name . ': ' . $value);
    }
}
