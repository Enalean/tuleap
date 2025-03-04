<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DefaultCloneURLSelectorTest extends TestCase
{
    private DefaultCloneURLSelector $selector;
    private CloneURLs&MockObject $clone_urls;

    protected function setUp(): void
    {
        $this->selector   = new DefaultCloneURLSelector();
        $this->clone_urls = $this->createMock(CloneURLs::class);
    }

    public function testSelectReturnsGerritUrlFirst(): void
    {
        $this->clone_urls->method('hasGerritUrl')->willReturn(true);
        $gerrit_url = 'ssh://gerrit_username@gerrit.example.com/repo';
        $this->clone_urls->method('getGerritUrl')->willReturn($gerrit_url);

        $result = $this->selector->select($this->clone_urls, UserTestBuilder::anActiveUser()->build());

        self::assertEquals($gerrit_url, $result->getUrl());
    }

    public function testSelectReturnsSshUrlWhenNoGerrit(): void
    {
        $this->clone_urls->method('hasGerritUrl')->willReturn(false);
        $this->clone_urls->method('hasSshUrl')->willReturn(true);
        $ssh_url = 'ssh://nikko.com/archfelon/untellable';
        $this->clone_urls->method('getSshUrl')->willReturn($ssh_url);

        $result = $this->selector->select($this->clone_urls, UserTestBuilder::anActiveUser()->build());

        self::assertEquals($ssh_url, $result->getUrl());
    }

    public function testSelectReturnsHttpsUrlWhenNoSsh(): void
    {
        $this->clone_urls->method('hasGerritUrl')->willReturn(false);
        $this->clone_urls->method('hasSshUrl')->willReturn(false);
        $this->clone_urls->method('hasHttpsUrl')->willReturn(true);
        $https_url = 'http://bataan.com/percher/equiproportionality';
        $this->clone_urls->method('getHttpsUrl')->willReturn($https_url);

        $result = $this->selector->select($this->clone_urls, UserTestBuilder::anActiveUser()->build());

        self::assertEquals($https_url, $result->getUrl());
    }

    public function testSelectReturnsHttpsWhenAnonymousUser(): void
    {
        $this->clone_urls->method('hasGerritUrl')->willReturn(false);
        $this->clone_urls->method('hasSshUrl')->willReturn(false);
        $this->clone_urls->method('hasHttpsUrl')->willReturn(true);
        $https_url = 'http://bataan.com/percher/equiproportionality';
        $this->clone_urls->method('getHttpsUrl')->willReturn($https_url);

        $result = $this->selector->select($this->clone_urls, UserTestBuilder::anAnonymousUser()->build());

        self::assertEquals($https_url, $result->getUrl());
    }

    public function testSelectThrowsWhenNoURL(): void
    {
        $this->clone_urls->method('hasGerritUrl')->willReturn(false);
        $this->clone_urls->method('hasSshUrl')->willReturn(false);
        $this->clone_urls->method('hasHttpsUrl')->willReturn(false);

        $this->expectException(NoCloneURLException::class);
        $this->selector->select($this->clone_urls, UserTestBuilder::anActiveUser()->build());
    }

    public function testSelectThrowsWhenAnonymousUserAndNoHttps(): void
    {
        $this->clone_urls->method('hasHttpsUrl')->willReturn(false);

        $this->expectException(NoCloneURLException::class);
        $this->selector->select($this->clone_urls, UserTestBuilder::anAnonymousUser()->build());
    }
}
