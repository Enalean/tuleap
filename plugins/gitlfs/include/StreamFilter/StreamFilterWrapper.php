<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\StreamFilter;

final class StreamFilterWrapper extends \php_user_filter
{
    /**
     * @var FilterInterface
     */
    private $user_filter;

    public function onCreate()
    {
        if (! $this->params instanceof FilterInterface) {
            throw new \InvalidArgumentException(
                'The stream filter wrapper expects an instance of a ' . FilterInterface::class . ' as parameter'
            );
        }
        $this->user_filter = $this->params;
        return true;
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = \stream_bucket_make_writeable($in)) {
            try {
                $bucket->data = $this->user_filter->process($bucket->data);
            } catch (\Exception $ex) {
                return PSFS_ERR_FATAL;
            }
            $consumed += $bucket->datalen;
            \stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }

    public function onClose(): void
    {
        $this->user_filter->filterDetachedEvent();
    }
}
