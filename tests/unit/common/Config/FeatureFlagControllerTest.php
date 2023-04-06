<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;

final class FeatureFlagControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const FEATURE_FLAG_KEY = 'stop_linking_artifact';

    protected function setUp(): void
    {
        ForgeConfig::set(ForgeConfig::FEATURE_FLAG_PREFIX . self::FEATURE_FLAG_KEY, 1);

        $this->feature_flag_controller = new FeatureFlagController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new NoopSapiEmitter(),
        );
    }

    private function expectedJSONError(string $reason): string
    {
        return json_encode(['error' => ['message' => $reason]], JSON_THROW_ON_ERROR);
    }

    public function testItReturns400IfTheFeatureFlagNameParamIsMissing(): void
    {
        $request = (new NullServerRequest())->withQueryParams(["some_param" => "feature_flag_not_feature_flag"]);

        $response = $this->feature_flag_controller->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($this->expectedJSONError('Bad request: the query param "name" is missing'), $response->getBody()->getContents());
    }

    public function testItReturns400IfTheFeatureFlagNameIsNotGiven(): void
    {
        $request = (new NullServerRequest())->withQueryParams(["name" => ""]);

        $response = $this->feature_flag_controller->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($this->expectedJSONError('Bad request: the name given is not a feature flag'), $response->getBody()->getContents());
    }

    public function testItReturns400IfNameGivenIsNotAFeatureFlag(): void
    {
        $request = (new NullServerRequest())->withQueryParams(["name" => "hehehe"]);

        $response = $this->feature_flag_controller->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($this->expectedJSONError('Bad request: the name given is not a feature flag'), $response->getBody()->getContents());
    }

    public function testItReturns400IfTheGivenFeatureFlagIsNotSet(): void
    {
        ForgeConfig::set(ForgeConfig::FEATURE_FLAG_PREFIX . 'feature_flag_not_feature_flag', null);

        $request = (new NullServerRequest())->withQueryParams(["name" => "feature_flag_not_feature_flag"]);

        $response = $this->feature_flag_controller->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($this->expectedJSONError('Bad request: the feature flag is not set'), $response->getBody()->getContents());
    }

    public function testItReturnsTheFeatureFlagValue(): void
    {
        $request = (new NullServerRequest())->withQueryParams(["name" => ForgeConfig::FEATURE_FLAG_PREFIX . self::FEATURE_FLAG_KEY]);

        $response = $this->feature_flag_controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(json_encode(['value' => 1]), $response->getBody()->getContents());
    }
}
