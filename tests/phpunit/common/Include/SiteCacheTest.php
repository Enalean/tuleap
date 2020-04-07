<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 *
 */

class SiteCacheTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\TemporaryTestDirectory;

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testItCreatesCacheDirectories()
    {
        $cache_dir    = $this->getTmpDir() . DIRECTORY_SEPARATOR . 'tuleap_cache_dir';
        $lang_dir     = $this->getTmpDir() . DIRECTORY_SEPARATOR . 'tuleap_lang_dir';

        ForgeConfig::set('codendi_cache_dir', $cache_dir);
        $logger              = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $GLOBALS['Language'] = \Mockery::spy(\BaseLanguage::class)->shouldReceive('getCacheDirectory')->andReturns($lang_dir)->getMock();
        $GLOBALS['HTML']     = \Mockery::spy(\Layout::class);

        $site_cache = new SiteCache($logger);
        $site_cache->restoreCacheDirectories();

        $this->assertDirectoryExists($cache_dir);
        $this->assertDirectoryExists($lang_dir);
    }
}
