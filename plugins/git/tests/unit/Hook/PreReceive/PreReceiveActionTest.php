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

use ForgeConfig;
use GitRepository;
use GitRepositoryFactory;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\WebAssembly\WASMCaller;
use Tuleap\ForgeConfigSandbox;

final class PreReceiveActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private \PHPUnit\Framework\MockObject\MockObject&GitRepositoryFactory $git_repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::setFeatureFlag(PreReceiveCommand::FEATURE_FLAG_KEY, '1');
        $this->git_repository_factory = $this->createStub(GitRepositoryFactory::class);
    }

    public function testRepoDoesNotExist(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = '{"rejection_message": null}';
            public function call(string $wasm_path, string $json_input): Option
            {
                $this->has_been_called = true;
                return Option::fromValue($this->output);
            }
        };

        $action = new PreReceiveAction($this->git_repository_factory, $ffi, new NullLogger());
        $this->git_repository_factory->method('getFromFullPath')->with('non_existing_repo_path')->willReturn(null);

        $this->expectException(PreReceiveRepositoryNotFoundException::class);
        $action->preReceiveExecute("non_existing_repo_path");
    }

    public function testWasmModuleDoesNotExist(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = '{"rejection_message": null}';

            public function call(string $wasm_path, string $json_input): Option
            {
                $this->has_been_called = true;
                return Option::fromValue($this->output);
            }
        };

        $action = new PreReceiveAction($this->git_repository_factory, $ffi, new NullLogger());

        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);
        $this->git_repository_factory->method('getFromFullPath')->with('existing_repo_path')->willReturn($git_repository);

        $result = $action->preReceiveExecute("existing_repo_path");

        self::assertFalse($ffi->has_been_called);
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }

    public function testThrowOnWasmtimeError(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = '{"internal_error": "this message describe the internal error"}';

            public function call(string $wasm_path, string $json_input): Option
            {
                $this->has_been_called = true;
                return Option::fromValue($this->output);
            }
        };

        $action = new PreReceiveAction($this->git_repository_factory, $ffi, new NullLogger());

        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);
        $this->git_repository_factory->method('getFromFullPath')->with('existing_repo_path')->willReturn($git_repository);

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $this->expectException(\RuntimeException::class);
        $action->preReceiveExecute("existing_repo_path");
    }

    public function testPushNotAccepted(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = '{"rejection_message": "this push is not accepted :("}';

            public function call(string $wasm_path, string $json_input): Option
            {
                $this->has_been_called = true;
                return Option::fromValue($this->output);
            }
        };

        $action = new PreReceiveAction($this->git_repository_factory, $ffi, new NullLogger());

        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);
        $this->git_repository_factory->method('getFromFullPath')->with('existing_repo_path')->willReturn($git_repository);

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path");

        self::assertTrue($ffi->has_been_called);
        self::assertTrue(Result::isErr($result));
        self::assertEquals('Rejection message: this push is not accepted :(', (string) $result->error);
    }

    public function testNormalBehaviour(): void
    {
        $ffi = new class implements WASMCaller {
            public bool $has_been_called = false;
            public string $output        = '{"rejection_message": null}';

            public function call(string $wasm_path, string $json_input): Option
            {
                $this->has_been_called = true;
                return Option::fromValue($this->output);
            }
        };

        $action = new PreReceiveAction($this->git_repository_factory, $ffi, new NullLogger());

        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);
        $this->git_repository_factory->method('getFromFullPath')->with('existing_repo_path')->willReturn($git_repository);

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path");

        self::assertTrue($ffi->has_been_called);
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }
}
