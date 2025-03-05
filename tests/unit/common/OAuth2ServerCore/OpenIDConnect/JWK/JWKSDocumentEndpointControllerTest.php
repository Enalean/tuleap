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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect\JWK;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectSigningKeyFactoryStaticForTestPurposes;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JWKSDocumentEndpointControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsResponse(): void
    {
        $controller = new JWKSDocumentEndpointController(
            new OpenIDConnectSigningKeyFactoryStaticForTestPurposes(),
            new \DateInterval('PT30S'),
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            $this->createMock(EmitterInterface::class)
        );

        $response = $controller->handle(new NullServerRequest());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('max-age=30,public', $response->getHeaderLine('Cache-Control'));
        $expected_json = '{"keys":[{"kty":"RSA","alg":"RS256","use":"sig","kid":"' . OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY_FINGERPRINT . '","n":"pVp45DC1lniS5l9yiR81OM3BCESDLyZYX3pXS32oJz0eOIqgA4mnqGNvupo_ARJnu1W_KVNNqxBNGno1oNLgV3GkHULBV-D4NDaX4064I0k1dk0HZBd8OG8QB0dwFoNFZ19SNrsEyq4xFn3CIysllfFE6GVQVht84_etmvO5-p4Dj6kUM4FO46jBXQBxSQs7ErE22m67CViu9ApDjZ1W9e7mHItPZfw0ldH6Y6-ZXfz8SBs_lblm_1BST1C7l_5vQtjStgHmiGlVL6CRIzyxDCJKYKP1r0FrwUEnMJEU1h-MyMSKPP9gzln8-icbhSvQF_eX6oZCfl-ibrC_nRZf2Q","e":"AQAB"}]}';
        $this->assertJsonStringEqualsJsonString($expected_json, $response->getBody()->getContents());
    }
}
