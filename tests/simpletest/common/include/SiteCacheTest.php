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
 */

class SiteCacheTest extends TuleapTestCase
{

    private $global_language;
    private $global_html;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        $this->global_language = $GLOBALS['Language'];
        $this->global_html     = $GLOBALS['HTML'];
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        $GLOBALS['Language'] = $this->global_language;
        $GLOBALS['HTML']     = $this->global_html;

        parent::tearDown();
    }

    public function itCreatesCacheDirectories()
    {
        $cache_dir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tuleap_cache_dir';
        $lang_dir     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tuleap_lang_dir';

        ForgeConfig::set('codendi_cache_dir', $cache_dir);
        $logger              = \Mockery::spy(\Logger::class);
        $language            = \Mockery::spy(\BaseLanguage::class)->shouldReceive('getCacheDirectory')->andReturns($lang_dir)->getMock();
        $GLOBALS['Language'] = $language;
        $html                = \Mockery::spy(\Layout::class);
        $GLOBALS['HTML']     = $html;

        $site_cache = new SiteCache($logger);
        $site_cache->restoreCacheDirectories();

        $this->assertTrue(is_dir($cache_dir));
        $this->assertTrue(is_dir($lang_dir));

        rmdir($cache_dir);
        rmdir($lang_dir);
    }
}
