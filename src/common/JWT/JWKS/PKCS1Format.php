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

final class PKCS1Format
{
    private const ASN1_INTEGER  = 2;
    private const ASN1_SEQUENCE = 48;
    // sequence(oid(1.2.840.113549.1.1.1), null))
    // MA0GCSqGSIb3DQEBAQUA
    private const RSA_OID       = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

    /**
     * @see http://tools.ietf.org/html/rfc3447#appendix-A.1.1
     *
     * @psalm-pure
     */
    public static function convertFromRSAModulusAndExponent(string $n, string $e): string
    {
        // modulus           INTEGER,  -- n
        $modulus_asn1  = pack('Ca*a*', self::ASN1_INTEGER, self::encodeLength($n), $n);
        // publicExponent    INTEGER   -- e
        $exponent_asn1 = pack('Ca*a*', self::ASN1_INTEGER, self::encodeLength($e), $e);

        $public_rsa = pack(
            'Ca*a*a*',
            self::ASN1_SEQUENCE,
            self::encodeLength($modulus_asn1 . $exponent_asn1),
            $modulus_asn1,
            $exponent_asn1
        );
        $public_rsa = chr(3) . self::encodeLength(chr(0) . $public_rsa) . chr(0) . $public_rsa;
        $public_rsa = pack('Ca*a*', self::ASN1_SEQUENCE, self::encodeLength(self::RSA_OID . $public_rsa), self::RSA_OID . $public_rsa);

        return "-----BEGIN PUBLIC KEY-----\r\n" . chunk_split(sodium_bin2base64($public_rsa, SODIUM_BASE64_VARIANT_ORIGINAL)) . '-----END PUBLIC KEY-----';
    }

    /**
     * X.690 (08/2015) paragraph 8.1.3
     * https://www.itu.int/rec/dologin_pub.asp?lang=e&id=T-REC-X.690-201508-I!!PDF-E&type=items
     *
     * To keep some sanity we manage lengths up to (2^8)^4
     *
     * @psalm-pure
     */
    private static function encodeLength(string $value): string
    {
        $length = strlen($value);
        if ($length <= 0x7F) {
            return chr($length);
        }

        $l = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($l), $l);
    }
}
