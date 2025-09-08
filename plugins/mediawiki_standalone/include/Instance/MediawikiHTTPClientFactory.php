<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Message\Authentication\Bearer;
use Psr\Http\Client\ClientInterface;
use Tuleap\Config\ConfigCannotBeModified;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\UnknownConfigKeyException;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;
use Tuleap\Http\HttpClientFactory;

#[ConfigKeyCategory('MediaWiki')]
final class MediawikiHTTPClientFactory implements MediawikiClientFactory
{
    private const TIMEOUT = 30;

    #[ConfigKey('Pre-shared secret between Tuleap and Mediawiki')]
    #[ConfigKeySecret]
    #[ConfigCannotBeModified]
    public const SHARED_SECRET = 'mediawiki_standalone_shared_secret';

    /**
     * @throws ConfigurationErrorException
     */
    #[\Override]
    public function getHTTPClient(): ClientInterface
    {
        try {
            return HttpClientFactory::createClientForInternalTuleapUseWithCustomTimeout(
                self::TIMEOUT,
                new AuthenticationPlugin(
                    new Bearer(hash_hmac('sha256', \ForgeConfig::getSecretAsClearText(self::SHARED_SECRET)->getString(), (string) time())),
                ),
                new HeaderDefaultsPlugin([
                    'Content-type' => 'application/json',
                ]),
            );
        } catch (UnknownConfigKeyException | CannotPerformIOOperationException | InvalidCiphertextException $e) {
            throw new ConfigurationErrorException(sprintf('Configuration error: %s (%s)', $e->getMessage(), $e::class), 0, $e);
        }
    }
}
