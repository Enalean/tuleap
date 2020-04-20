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

namespace Tuleap\JWT\JWKS;

use PHPUnit\Framework\TestCase;

final class PKCS1FormatTest extends TestCase
{
    private const N_BASE64_URLSAFE = '6ffRDU_iebFRDNAQKi4hogWK4QGoPN7vOoUgrEbNX86yY4lI5cscvB74PYmmkEDLMqe2CpmcBPc1ZDbVkrFfqc66FxNgjn5VOL2mbD_pSEGAqcvyujc36efrRdA8lhFxqABhfHvV4GXIOuYZYADnKJVENNivLLnt4ozD4JFT1VVAwZkVRLMnRE5lPgISyhPYq5xqjiUztybi57t2kqxETleL9Qyc_moTKdyxxB7f4ujhS3yYcSzVycaz6PptOYBV3Dx9RAZwlU6_lY-HCB4qPYGBVqtW4_yDPx_E6KYEkmrNcyhEBkxx6grBLupYH12a4My5EnsMeneX-qUG4Y62SQGpI7fvlizeyvhd9LQQgjwTGnioDasD2CR0AfFdNcYM1V1GHJac5VaZ4So7rcatzKz1tUSpNb9N8pRDFXiAL3AlVn-jk3VBSrLci3KqIXeu_bzfD3c6j4aLUtuTd2VjE29Ul3qsqGkGJZt25QsObC-tgq5JGwEbZ13p1r4ooaqBCIQJLiSjVanbtgT_eLCGybcmEtGTPrssB-tRmxSrG-CACGwAaj1ieBth9RlLG2Y_dALA0DSQpAM3erG6jNgrd3Yeur7pFE6Pwf_BFMIQYFvYWdH6TpUZwUF-eP5QG3yxytmj1txf2f0J7wgNeUrv8mMqxl-Rt966abyv28Dn7Nc';
    private const E_BASE64_URLSAFE = 'AQAB';


    public function testConversion(): void
    {
        $n = sodium_base642bin(self::N_BASE64_URLSAFE, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $e = sodium_base642bin(self::E_BASE64_URLSAFE, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

        $pem = PKCS1Format::convertFromRSAModulusAndExponent($n, $e);

        $public_key         = openssl_get_publickey($pem);
        $public_key_details = openssl_pkey_get_details($public_key);

        $this->assertEquals($n, $public_key_details['rsa']['n']);
        $this->assertEquals($e, $public_key_details['rsa']['e']);
    }
}
