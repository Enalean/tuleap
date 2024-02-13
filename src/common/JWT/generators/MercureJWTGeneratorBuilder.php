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
declare(strict_types=1);

namespace Tuleap\JWT\generators;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\RealTimeMercure\MercureClient;

class MercureJWTGeneratorBuilder
{
    public const  DEFAULTPATH = '/etc/tuleap/conf/mercure.env';
    public static function build(string $path): MercureJWTGenerator
    {
        if (
            ! \ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY) &&
            ! \ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)
        ) {
            return new NullMercureJWTGenerator();
        }
        $mercure_file_content =  @file_get_contents($path);
        if ($mercure_file_content === false || ! str_starts_with($mercure_file_content, "MERCURE_KEY=")) {
            return new NullMercureJWTGenerator();
        }
        $mercure_key = new ConcealedString(trim(substr($mercure_file_content, 12)));
        if (strlen($mercure_key->getString()) <= 100) {
            return new NullMercureJWTGenerator();
        }
        return new MercureJWTGeneratorImpl(
            Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText($mercure_key->getString())),
        );
    }
}
