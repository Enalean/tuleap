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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2Server\OpenIDConnect\IDToken\OpenIDConnectSigningKeyFactory;

final class JWKSDocumentEndpointControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function testBuildsResponse(): void
    {
        $signing_key_factory = \Mockery::mock(OpenIDConnectSigningKeyFactory::class);
        $controller = new JWKSDocumentEndpointController(
            $signing_key_factory,
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            \Mockery::mock(EmitterInterface::class)
        );

        $signing_key_factory->shouldReceive('getPublicKey')->andReturn(self::PUBLIC_KEY);

        $response = $controller->handle(new NullServerRequest());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('max-age=1800,public', $response->getHeaderLine('Cache-Control'));
        $expected_json = '{"keys":[{"kty":"RSA","alg":"RS256","use":"sig","kid":"8dd7edd5ac158cf526babc4dd44fd569ab482081e0124739c5520b74cf36f0c9","n":"6ffRDU_iebFRDNAQKi4hogWK4QGoPN7vOoUgrEbNX86yY4lI5cscvB74PYmmkEDLMqe2CpmcBPc1ZDbVkrFfqc66FxNgjn5VOL2mbD_pSEGAqcvyujc36efrRdA8lhFxqABhfHvV4GXIOuYZYADnKJVENNivLLnt4ozD4JFT1VVAwZkVRLMnRE5lPgISyhPYq5xqjiUztybi57t2kqxETleL9Qyc_moTKdyxxB7f4ujhS3yYcSzVycaz6PptOYBV3Dx9RAZwlU6_lY-HCB4qPYGBVqtW4_yDPx_E6KYEkmrNcyhEBkxx6grBLupYH12a4My5EnsMeneX-qUG4Y62SQGpI7fvlizeyvhd9LQQgjwTGnioDasD2CR0AfFdNcYM1V1GHJac5VaZ4So7rcatzKz1tUSpNb9N8pRDFXiAL3AlVn-jk3VBSrLci3KqIXeu_bzfD3c6j4aLUtuTd2VjE29Ul3qsqGkGJZt25QsObC-tgq5JGwEbZ13p1r4ooaqBCIQJLiSjVanbtgT_eLCGybcmEtGTPrssB-tRmxSrG-CACGwAaj1ieBth9RlLG2Y_dALA0DSQpAM3erG6jNgrd3Yeur7pFE6Pwf_BFMIQYFvYWdH6TpUZwUF-eP5QG3yxytmj1txf2f0J7wgNeUrv8mMqxl-Rt966abyv28Dn7Nc","e":"AQAB"}]}';
        $this->assertJsonStringEqualsJsonString($expected_json, $response->getBody()->getContents());
    }
}
