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

namespace Tuleap\password\HaveIBeenPwned;

/**
 * @see https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange
 */
class PwnedPasswordRangeRetriever
{
    /**
     * Set a low timeout for HTTP requests made to the API.
     * Even if the API becomes slow or inaccessible the user
     * experience will not be impacted too much.
     */
    const AGGRESSIVE_TIMEOUT_FOR_HTTP_REQUEST = 2;
    const ENDPOINT                            = 'https://api.pwnedpasswords.com/range/';

    public function __construct(\Http_Client $http_client, \Logger $logger)
    {
        $this->http_client = $http_client;
        $this->http_client->setOption(CURLOPT_TIMEOUT, self::AGGRESSIVE_TIMEOUT_FOR_HTTP_REQUEST);
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getHashSuffixesMatchingPrefix($sha1_password_prefix)
    {
        if (strlen($sha1_password_prefix) !== PwnedPasswordChecker::PREFIX_SIZE) {
            throw new \LengthException(
                'Prefix transmitted to the HIBP Password API must be ' . PwnedPasswordChecker::PREFIX_SIZE . ' char not ' . strlen($sha1_password_prefix)
            );
        }

        $url = self::ENDPOINT . urlencode($sha1_password_prefix);

        $this->http_client->setOption(CURLOPT_URL, $url);

        try {
            $this->http_client->doRequest();
        } catch (\Http_ClientException $ex) {
            $this->logger->info('Call to HIBP Password API failed: ' . $ex->getMessage());
            return '';
        }

        return $this->http_client->getLastResponse();
    }
}
