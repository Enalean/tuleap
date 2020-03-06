<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Password\HaveIBeenPwned;

use Psr\Log\LoggerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @see https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange
 */
class PwnedPasswordRangeRetriever
{
    private const ENDPOINT = 'https://api.pwnedpasswords.com/range/';
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ClientInterface $http_client, RequestFactoryInterface $request_factory, LoggerInterface $logger)
    {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
        $this->logger          = $logger;
    }

    public function getHashSuffixesMatchingPrefix(string $sha1_password_prefix) : string
    {
        if (strlen($sha1_password_prefix) !== PwnedPasswordChecker::PREFIX_SIZE) {
            throw new \LengthException(
                'Prefix transmitted to the HIBP Password API must be ' . PwnedPasswordChecker::PREFIX_SIZE . ' char not ' . strlen($sha1_password_prefix)
            );
        }

        $url = self::ENDPOINT . urlencode($sha1_password_prefix);

        $request = $this->request_factory->createRequest('GET', $url)->withHeader('Add-Padding', 'true');

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $ex) {
            $this->logger->info('Call to HIBP Password API failed: ' . $ex->getMessage());
            return '';
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->info('Response from the HIBP Password API is invalid: ' .
                $response->getStatusCode() . ' ' . $response->getReasonPhrase());
            return '';
        }

        return $response->getBody()->getContents();
    }
}
