<?php
/**
 * Copyright (c) Enalean, 2011 - 2019. All Rights Reserved.
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

namespace Tuleap;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;

require_once __DIR__ . '/../../../../src/www/include/service.php';

class ServiceCreateServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp() : void
    {
        $this->template = [
            'name' => 'template-name',
            'id'   => 120
        ];
        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getGroupId')->andReturn(101);
        $this->project->shouldReceive('getUnixName')->andReturn('h1tst');
    }

    private function assertLinkEquals($link, $expected)
    {
        $result = service_replace_template_name_in_link($link, $this->template, $this->project);
        $this->assertSame($expected, $result);
    }

    public function testItReplacesNameIfLinkIsDashboard()
    {
        $link     = '/projects/template-name/';
        $expected = '/projects/h1tst/';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItReplacesNameIfLinkContainesAmpersand()
    {
        $link     = 'test=template-name&group=template-name';
        $expected = 'test=template-name&group=h1tst';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItReplacesGroupId()
    {
        $link     = '/www/?group_id=120';
        $expected = '/www/?group_id=101';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItDoesntReplaceGroupIdIfNoMatch()
    {
        $link     = '/www/?group_id=1204'; //template id is 120
        $expected = '/www/?group_id=1204';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItReplacesWebroot()
    {
        $link     = '/www/template-name/';
        $expected = '/www/h1tst/';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItReplacesWhenUsedAsQueryParameter()
    {
        $link     = 'group=template-name';
        $expected = 'group=h1tst';
        $this->assertLinkEquals($link, $expected);
    }

    public function testItDoesntReplaceWhenNameIsPartOfAPluginName()
    {
        $this->template['name'] = 'agile';
        $link     = '/plugins/agiledashboard/';
        $expected = '/plugins/agiledashboard/';
        $this->assertLinkEquals($link, $expected);
    }
}
