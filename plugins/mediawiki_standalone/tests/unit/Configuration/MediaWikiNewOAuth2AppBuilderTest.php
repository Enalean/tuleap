<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiNewOAuth2AppBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testBuildsNewMediawikiOAuth2App(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');
        \ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'MyTuleapInstance');

        $builder = new MediaWikiNewOAuth2AppBuilder(new SplitTokenVerificationStringHasher());

        $app = $builder->buildMediawikiOAuth2App();

        self::assertStringContainsString('MyTuleapInstance', $app->getName());
        self::assertEquals('plugin_mediawiki_standalone', $app->getAppType());
        self::assertEquals('https://example.com/mediawiki/_oauth/Special:TuleapLogin/callback', $app->getRedirectEndpoint());
    }
}
