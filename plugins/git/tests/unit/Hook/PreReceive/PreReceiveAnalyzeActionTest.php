<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use GitRepository;
use GitRepositoryFactory;
use Tuleap\WebAssembly\WASMCaller;

final class PreReceiveAnalyzeActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|GitRepositoryFactory $git_repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
    }

    public function testRepoDoesNotExist(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = "testoutput";

            public function call(string $json_input): string
            {
                $this->has_been_called = true;
                return $this->output;
            }
        };

        $action = new PreReceiveAnalyzeAction($this->git_repository_factory, $ffi);

        $this->git_repository_factory->method('getRepositoryById')->with(666)->willReturn(null);

        $this->expectException(PreReceiveRepositoryNotFoundException::class);
        $action->preReceiveAnalyse('666', ['aaaaaaa', 'aaaaaaa', 'refs/heads/master']);
    }

    public function testNormalBehaviour(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = "testoutput";

            public function call(string $json_input): string
            {
                $this->has_been_called = true;
                return $this->output;
            }
        };

        $action = new PreReceiveAnalyzeAction($this->git_repository_factory, $ffi);

        $git_repository = $this->createMock(GitRepository::class);
        $this->git_repository_factory->method('getRepositoryById')->with(42)->willReturn($git_repository);

        $output = $action->preReceiveAnalyse('42', ["0000000000000000000000000000000000000000", "193e60ca836ae22a0545d55c5e06cfc48dccd23d", 'refs/heads/master']);

        self::assertTrue($ffi->has_been_called);
        self::assertEquals($output, $ffi->output);
    }
}
