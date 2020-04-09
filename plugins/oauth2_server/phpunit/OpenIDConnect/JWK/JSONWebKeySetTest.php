<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\OpenIDConnect\JWK;

use PHPUnit\Framework\TestCase;

final class JSONWebKeySetTest extends TestCase
{
    private const PUBLIC_KEY = <<<EOT
        -----BEGIN PUBLIC KEY-----
        MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA6ffRDU/iebFRDNAQKi4h
        ogWK4QGoPN7vOoUgrEbNX86yY4lI5cscvB74PYmmkEDLMqe2CpmcBPc1ZDbVkrFf
        qc66FxNgjn5VOL2mbD/pSEGAqcvyujc36efrRdA8lhFxqABhfHvV4GXIOuYZYADn
        KJVENNivLLnt4ozD4JFT1VVAwZkVRLMnRE5lPgISyhPYq5xqjiUztybi57t2kqxE
        TleL9Qyc/moTKdyxxB7f4ujhS3yYcSzVycaz6PptOYBV3Dx9RAZwlU6/lY+HCB4q
        PYGBVqtW4/yDPx/E6KYEkmrNcyhEBkxx6grBLupYH12a4My5EnsMeneX+qUG4Y62
        SQGpI7fvlizeyvhd9LQQgjwTGnioDasD2CR0AfFdNcYM1V1GHJac5VaZ4So7rcat
        zKz1tUSpNb9N8pRDFXiAL3AlVn+jk3VBSrLci3KqIXeu/bzfD3c6j4aLUtuTd2Vj
        E29Ul3qsqGkGJZt25QsObC+tgq5JGwEbZ13p1r4ooaqBCIQJLiSjVanbtgT/eLCG
        ybcmEtGTPrssB+tRmxSrG+CACGwAaj1ieBth9RlLG2Y/dALA0DSQpAM3erG6jNgr
        d3Yeur7pFE6Pwf/BFMIQYFvYWdH6TpUZwUF+eP5QG3yxytmj1txf2f0J7wgNeUrv
        8mMqxl+Rt966abyv28Dn7NcCAwEAAQ==
        -----END PUBLIC KEY-----
        EOT;

    public function testBuilds(): void
    {
        $jwk = JSONWebKey::fromPEMRSAPublicKeyForSignature(self::PUBLIC_KEY);

        $set = new JSONWebKeySet($jwk);

        $this->assertEquals([$jwk], $set->keys);
    }
}
