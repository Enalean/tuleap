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

use CuyZ\Valinor\MapperBuilder;
use ForgeConfig;
use GitRepository;
use GitRepositoryFactory;
use org\bovigo\vfs\vfsStream;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\WebAssembly\WASMCaller;
use Tuleap\ForgeConfigSandbox;
use Tuleap\WebAssembly\WASMCallerStub;

final class PreReceiveActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const PRE_RECEIVE_HOOK_INPUT           = 'aaa bbb refs/heads/main';
    private const TECHNICAL_PRE_RECEIVE_HOOK_INPUT = 'aaa bbb refs/tlpr/42/head';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRepoDoesNotExist(): void
    {
        $wasm_caller = WASMCallerStub::successfulWasmCall('');

        $action = $this->buildPreReceiveAction(null, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $result = $action->preReceiveExecute("non_existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);

        self::assertFalse($wasm_caller->hasBeenCalled());
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }

    public function testWasmModuleDoesNotExist(): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::successfulWasmCall('');

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $result = $action->preReceiveExecute("existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);

        self::assertFalse($wasm_caller->hasBeenCalled());
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }

    public function testRejectsCallWhenWASMExecutionDoesNotSucceed(): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::failingWasmCall('Some error');

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);
        self::assertTrue(Result::isErr($result));
    }

    /**
     * @dataProvider dataProviderInvalidResponseWASMCall
     */
    public function testRejectPushWhenReceivingAnIncorrectlyFormattedResponseFromTheWASMCall(string $invalid_response): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::successfulWasmCall($invalid_response);

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);
        self::assertTrue(Result::isErr($result));
    }

    public static function dataProviderInvalidResponseWASMCall(): array
    {
        return [
            'Incorrect format' => ['{"not_valid": true}'],
            'Not valid JSON' => ['{Not valid JSON string'],
        ];
    }

    public function testPushNotAccepted(): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::successfulWasmCall('{"rejection_message": "this push is not accepted :("}');

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);

        self::assertTrue($wasm_caller->hasBeenCalled());
        self::assertTrue(Result::isErr($result));
        self::assertEquals('this push is not accepted :(', (string) $result->error);
    }

    public function testPushWithOnlyTechnicalReferencesIsAccepted(): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::successfulWasmCall('{"rejection_message": null}');

        $event_dispatcher = EventDispatcherStub::withCallback(function (MarkTechnicalReference $event): MarkTechnicalReference {
            $event->markAsTechnical();
            return $event;
        });

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, $event_dispatcher);

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path", self::TECHNICAL_PRE_RECEIVE_HOOK_INPUT);

        self::assertFalse($wasm_caller->hasBeenCalled());
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }

    public function testNormalBehaviour(): void
    {
        $git_repository = $this->createStub(GitRepository::class);
        $git_repository->method('getId')->willReturn(42);

        $wasm_caller = WASMCallerStub::successfulWasmCall('{"rejection_message": null}');

        $action = $this->buildPreReceiveAction($git_repository, $wasm_caller, EventDispatcherStub::withIdentityCallback());

        $structure = [
            'untrusted-code' => [
                'git' => [
                    'pre-receive-hook' => [ '42.wasm' => 'definitely a wasm file'],
                ],
            ],
        ];
        $root      = vfsStream::setup('root', null, $structure);
        ForgeConfig::set('sys_data_dir', $root->url());

        $result = $action->preReceiveExecute("existing_repo_path", self::PRE_RECEIVE_HOOK_INPUT);

        self::assertTrue($wasm_caller->hasBeenCalled());
        self::assertTrue(Result::isOk($result));
        self::assertEquals(null, $result->value);
    }

    private function buildPreReceiveAction(?GitRepository $git_repository, WASMCaller $wasm_caller, EventDispatcherInterface $event_dispatcher): PreReceiveAction
    {
        $git_repository_factory = $this->createStub(GitRepositoryFactory::class);
        $git_repository_factory->method('getFromFullPath')->willReturn($git_repository);

        return new PreReceiveAction(
            $git_repository_factory,
            $wasm_caller,
            (new MapperBuilder())->mapper(),
            new NullLogger(),
            $event_dispatcher
        );
    }
}
