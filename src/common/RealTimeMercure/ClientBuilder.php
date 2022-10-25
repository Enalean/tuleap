<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
namespace Tuleap\RealTimeMercure;

use BackendLogger;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\JWT\generators\MercureJWTGenerator;
use UserManager;

class ClientBuilder
{
    public const  DEFAULTPATH = '/etc/tuleap/conf/mercure.env';
    public static function build(string $path): Client
    {
        $mercure_file = fopen($path, 'r');
        if (! $mercure_file) {
            return new NullClient();
        }
        $mercure_file_content = fgets($mercure_file);
        if (! str_contains($mercure_file_content, 'MERCURE_KEY=')) {
            return new NullClient();
        }
        /** @var non-empty-string $mercure_key */
        $mercure_key = str_replace("\n", '', substr($mercure_file_content, 12));
        if (strlen($mercure_key) <= 100) {
            return new NullClient();
        }
        $mercure_jwt_generator = new MercureJWTGenerator(
            Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText($mercure_key)),
            UserManager::instance(),
        );
        return new MercureClient(
            HttpClientFactory::createClientForInternalTuleapUse(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger(),
            $mercure_jwt_generator
        );
    }
}
