<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DefaultCloneURLSelectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefaultCloneURLSelector
     */
    private $selector;
    /**
     * @var CloneURLs | Mockery\MockInterface
     */
    private $clone_urls;
    /**
     * @var \PFUser | Mockery\MockInterface
     */
    private $current_user;

    protected function setUp(): void
    {
        $this->selector     = new DefaultCloneURLSelector();
        $this->clone_urls   = Mockery::mock(CloneURLs::class);
        $this->current_user = Mockery::mock(\PFUser::class);
    }

    public function testSelectReturnsGerritUrlFirst()
    {
        $this->clone_urls->shouldReceive('hasGerritUrl')->andReturnTrue();
        $gerrit_url = 'ssh://gerrit_username@gerrit.example.com/repo';
        $this->clone_urls->shouldReceive('getGerritUrl')->andReturn($gerrit_url);
        $this->current_user->shouldReceive('isAnonymous')->andReturnFalse();

        $result = $this->selector->select($this->clone_urls, $this->current_user);

        $this->assertEquals($gerrit_url, $result->getUrl());
    }

    public function testSelectReturnsSshUrlWhenNoGerrit()
    {
        $this->clone_urls->shouldReceive('hasGerritUrl')->andReturnFalse();
        $this->clone_urls->shouldReceive('hasSshUrl')->andReturnTrue();
        $ssh_url = 'ssh://nikko.com/archfelon/untellable';
        $this->clone_urls->shouldReceive('getSshUrl')->andReturn($ssh_url);
        $this->current_user->shouldReceive('isAnonymous')->andReturnFalse();

        $result = $this->selector->select($this->clone_urls, $this->current_user);

        $this->assertEquals($ssh_url, $result->getUrl());
    }

    public function testSelectReturnsHttpsUrlWhenNoSsh()
    {
        $this->clone_urls->shouldReceive('hasGerritUrl', 'hasSshUrl')->andReturnFalse();
        $this->clone_urls->shouldReceive('hasHttpsUrl')->andReturnTrue();
        $https_url = 'http://bataan.com/percher/equiproportionality';
        $this->clone_urls->shouldReceive('getHttpsUrl')->andReturn($https_url);
        $this->current_user->shouldReceive('isAnonymous')->andReturnFalse();

        $result = $this->selector->select($this->clone_urls, $this->current_user);

        $this->assertEquals($https_url, $result->getUrl());
    }

    public function testSelectReturnsHttpsWhenAnonymousUser()
    {
        $this->clone_urls->shouldReceive('hasGerritUrl', 'hasSshUrl')->andReturnFalse();
        $this->clone_urls->shouldReceive('hasHttpsUrl')->andReturnTrue();
        $https_url = 'http://bataan.com/percher/equiproportionality';
        $this->clone_urls->shouldReceive('getHttpsUrl')->andReturn($https_url);
        $this->current_user->shouldReceive('isAnonymous')->andReturnTrue();

        $result = $this->selector->select($this->clone_urls, $this->current_user);

        $this->assertEquals($https_url, $result->getUrl());
    }

    public function testSelectThrowsWhenNoURL()
    {
        $this->clone_urls->shouldReceive('hasGerritUrl', 'hasSshUrl', 'hasHttpsUrl')->andReturnFalse();
        $this->current_user->shouldReceive('isAnonymous')->andReturnFalse();

        $this->expectException(NoCloneURLException::class);
        $this->selector->select($this->clone_urls, $this->current_user);
    }

    public function testSelectThrowsWhenAnonymousUserAndNoHttps()
    {
        $this->clone_urls->shouldReceive('hasHttpsUrl')->andReturnFalse();
        $this->current_user->shouldReceive('isAnonymous')->andReturnTrue();

        $this->expectException(NoCloneURLException::class);
        $this->selector->select($this->clone_urls, $this->current_user);
    }
}
