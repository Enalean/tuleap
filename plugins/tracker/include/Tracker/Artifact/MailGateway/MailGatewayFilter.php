<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

class MailGatewayFilter
{
    public function isAnAutoReplyMail($raw_mail)
    {
        return $this->isAutoSubmittedHeaderSent($raw_mail) === true
            || $this->isReturnPathUndefined($raw_mail) === true;
    }

    /**
     * @see https://tools.ietf.org/search/rfc3834
     * @return bool
     */
    private function isAutoSubmittedHeaderSent($raw_mail)
    {
        return isset($raw_mail['headers']['auto-submitted']) && $raw_mail['headers']['auto-submitted'] !== 'no';
    }

    /**
     * @see https://tools.ietf.org/search/rfc3834
     * @return bool
     */
    private function isReturnPathUndefined($raw_mail)
    {
        return isset($raw_mail['headers']['return-path']) && $raw_mail['headers']['return-path'] === '<>';
    }
}
