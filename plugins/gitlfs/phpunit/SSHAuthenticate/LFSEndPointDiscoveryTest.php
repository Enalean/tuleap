<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\SSHAuthenticate;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class LFSEndPointDiscoveryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LFSEndPointDiscovery
     */
    private $endpoint_discovery;
    private $git_repository;

    protected function setUp() : void
    {
        parent::setUp();

        $this->git_repository = \Mockery::mock(\GitRepository::class, ['getFullHTTPUrlWithDotGit' => 'https://tuleap.example.com/plugins/git/foo/bar.git']);

        $this->endpoint_discovery = new LFSEndPointDiscovery($this->git_repository);
    }

    public function testItHasAnHref()
    {
        $this->assertEquals('https://tuleap.example.com/plugins/git/foo/bar.git/info/lfs', $this->endpoint_discovery->getHref());
    }
}
