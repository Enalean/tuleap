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

final class JSONWebKeyTest extends TestCase
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


    public function testBuildsJWKSigningKeyFromRSAPEM(): void
    {
        $jwk = JSONWebKey::fromPEMRSAPublicKeyForSignature(self::PUBLIC_KEY);

        $this->assertEquals('sig', $jwk->use);
        $this->assertEquals('RS256', $jwk->alg);
        $this->assertEquals('8dd7edd5ac158cf526babc4dd44fd569ab482081e0124739c5520b74cf36f0c9', $jwk->kid);
        $this->assertEquals('6ffRDU_iebFRDNAQKi4hogWK4QGoPN7vOoUgrEbNX86yY4lI5cscvB74PYmmkEDLMqe2CpmcBPc1ZDbVkrFfqc66FxNgjn5VOL2mbD_pSEGAqcvyujc36efrRdA8lhFxqABhfHvV4GXIOuYZYADnKJVENNivLLnt4ozD4JFT1VVAwZkVRLMnRE5lPgISyhPYq5xqjiUztybi57t2kqxETleL9Qyc_moTKdyxxB7f4ujhS3yYcSzVycaz6PptOYBV3Dx9RAZwlU6_lY-HCB4qPYGBVqtW4_yDPx_E6KYEkmrNcyhEBkxx6grBLupYH12a4My5EnsMeneX-qUG4Y62SQGpI7fvlizeyvhd9LQQgjwTGnioDasD2CR0AfFdNcYM1V1GHJac5VaZ4So7rcatzKz1tUSpNb9N8pRDFXiAL3AlVn-jk3VBSrLci3KqIXeu_bzfD3c6j4aLUtuTd2VjE29Ul3qsqGkGJZt25QsObC-tgq5JGwEbZ13p1r4ooaqBCIQJLiSjVanbtgT_eLCGybcmEtGTPrssB-tRmxSrG-CACGwAaj1ieBth9RlLG2Y_dALA0DSQpAM3erG6jNgrd3Yeur7pFE6Pwf_BFMIQYFvYWdH6TpUZwUF-eP5QG3yxytmj1txf2f0J7wgNeUrv8mMqxl-Rt966abyv28Dn7Nc', $jwk->n);
        $this->assertEquals('AQAB', $jwk->e);
    }

    public function testDoesNotBuildJWKFromStringNotPEMFormatted(): void
    {
        $this->expectException(InvalidPublicRSAKeyPEMFormatException::class);
        JSONWebKey::fromPEMRSAPublicKeyForSignature('wrong_format');
    }

    public function testDoesNotBuildJWKFromKeyThatIsNotRSA(): void
    {
        $ec_key = <<<EOT
            -----BEGIN PUBLIC KEY-----
            MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEWYhqRNr+sXtaXqlrzBJzzEO4A40X
            2pw5CIjOcJ3A54RSmmYBhHmJBXgk6BwVcLQG/GM+E4u5n9sQMqa5poBT9A==
            -----END PUBLIC KEY-----
            EOT;

        $this->expectException(InvalidPublicRSAKeyPEMFormatException::class);
        JSONWebKey::fromPEMRSAPublicKeyForSignature($ec_key);
    }
}
