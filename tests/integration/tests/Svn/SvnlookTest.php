<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Svn;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use SVN_Svnlook;
use Tuleap\TemporaryTestDirectory;

final class SvnlookTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var SVN_Svnlook
     */
    private $svnlook;

    protected function setUp(): void
    {
        $project_name  = 'svnrepo';
        $this->project = \Mockery::spy(Project::class);
        $this->project->shouldReceive('getSVNRootPath')->andReturn($this->getTmpDir() . '/' . $project_name);

        $svnrepo = $this->getTmpDir() . '/' . $project_name;

        exec("svnadmin create $svnrepo");
        exec("svn mkdir --username donald_duck --parents -m 'this is 1.0' file://$svnrepo/tags/1.0");
        exec("svn mkdir --parents -m 'that is 2.0' file://$svnrepo/tags/2.0");

        $this->svnlook = new SVN_Svnlook();
    }

    public function testItGetADirectoryContents(): void
    {
        $tags = $this->svnlook->getDirectoryListing($this->project, '/tags');
        $tags = array_values($tags);
        sort($tags);
        $this->assertEquals(['/tags/', '/tags/1.0/', '/tags/2.0/'], $tags);
    }

    public function testItGetsTheTree(): void
    {
        $tree = $this->svnlook->getTree($this->project);

        $expected = array('/', 'tags/', 'tags/2.0/', 'tags/1.0/');

        $this->assertEqualsCanonicalizing($expected, $tree);
    }

    public function testItGetsHistoryOfAPath(): void
    {
        $this->assertEquals(
            array(
                'REVISION   PATH',
                '--------   ----',
                '       2   /tags',
            ),
            $this->svnlook->getPathLastHistory($this->project, '/tags')
        );
    }

    public function testItGetsTheLogForARevision(): void
    {
        $expected_message = 'this is 1.0';
        $log = $this->svnlook->getInfo($this->project, 1);
        $this->assertCount(4, $log);
        $this->assertEquals('donald_duck', $log[0]);
        $this->assertEquals(strlen($expected_message), $log[2]);
        $this->assertEquals($expected_message, $log[3]);

        // Date
        $str_date = substr($log[1], 0, strpos($log[1], '('));
        $log_timestamp = strtotime($str_date);

        // Same year-month-day
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', $log_timestamp));
    }
}
