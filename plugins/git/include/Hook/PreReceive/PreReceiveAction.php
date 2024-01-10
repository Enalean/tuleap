<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use ForgeConfig;
use GitRepositoryFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\WebAssembly\WASMCaller;
use Tuleap\WebAssembly\WASMModuleMountPoint;

final class PreReceiveAction
{
    public function __construct(
        private readonly GitRepositoryFactory $git_repository_factory,
        private readonly WASMCaller $wasm_caller,
        private readonly TreeMapper $mapper,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    /**
     * @psalm-taint-escape file
     */
    private function getPossibleCustomPreReceiveHookPath(\GitRepository $repository): string
    {
        return ForgeConfig::get('sys_data_dir') . '/untrusted-code/git/pre-receive-hook/' . (int) $repository->getId() . '.wasm';
    }

    /**
     * Analyze information related to a git object reference
     * @psalm-param non-empty-string $repository_path
     * @psalm-return Ok<null>|Err<Fault>
     */
    public function preReceiveExecute(string $repository_path, string $input_data): Ok|Err
    {
        $repository = $this->git_repository_factory->getFromFullPath($repository_path);
        if ($repository === null) {
            return Result::ok(null);
        }

        $ids = ForgeConfig::getFeatureFlag(PreReceiveCommand::FEATURE_FLAG_KEY);
        if ($ids) {
            $ignored_repos_ids = array_map(static fn(string $value) => (int) trim($value), explode(',', $ids));
            if (in_array($repository->getId(), $ignored_repos_ids, true)) {
                return Result::ok(null);
            }
        }

        $wasm_path = $this->getPossibleCustomPreReceiveHookPath($repository);
        if (! is_file($wasm_path)) {
            return Result::ok(null);
        }

        $this->logger->debug("[pre-receive] Monitoring updated refs for: '$repository_path'");
        $guest_dir_path = "/repo-git-" . random_int(1000, 9999);
        return PreReceiveHookData::fromRawStdinHook($input_data, $repository_path, $guest_dir_path, $this->logger)
            ->map(
                fn (PreReceiveHookData $hook_data): PreReceiveHookDataWithoutTechnicalReference => PreReceiveHookDataWithoutTechnicalReference::fromHookData($hook_data, $this->event_dispatcher, $this->logger)
            )
            ->andThen(
                /** @psalm-return Ok<null>|Err<Fault> */
                function (PreReceiveHookDataWithoutTechnicalReference $hook_result) use ($wasm_path, $repository_path, $guest_dir_path): Ok|Err {
                    if (empty($hook_result->updated_references)) {
                        return Result::ok(null);
                    }
                    $json_in      = json_encode($hook_result, JSON_THROW_ON_ERROR);
                    $mount_points = [new WASMModuleMountPoint($repository_path, $guest_dir_path)];
                    return $this->wasm_caller->call($wasm_path, $json_in, $mount_points)->mapOr(
                        $this->processResponse(...),
                        Result::ok(null)
                    );
                }
            );
    }

    /**
     * @psalm-param Ok<string>|Err<Fault> $wasm_response
     * @psalm-return Ok<null>|Err<Fault>
     */
    private function processResponse(Ok|Err $wasm_response): Ok|Err
    {
        return $wasm_response->match(
            /** @psalm-return Ok<PreReceiveHookResponse>|Err<Fault> */
            function (string $prereceive_json_response): Ok|Err {
                try {
                    return Result::ok(
                        $this->mapper->map(
                            PreReceiveHookResponse::class,
                            Source::json($prereceive_json_response)
                        )
                    );
                } catch (MappingError | \RuntimeException $mapping_error) {
                    return Result::err(
                        Fault::fromThrowableWithMessage(
                            $mapping_error,
                            'An invalid response has been received from the pre-receive hook, please contact your administrator for more information'
                        )
                    );
                }
            },
            /** @psalm-return Err<Fault> */
            function (Fault $wasm_call_fault): Err {
                Fault::writeToLogger($wasm_call_fault, $this->logger, LogLevel::WARNING);
                return Result::err(
                    Fault::fromMessage("An error has occurred while running the pre-receive hook, please contact your administrator for more information")
                );
            }
        )->andThen(
            function (PreReceiveHookResponse $pre_receive_hook_response): Ok|Err {
                if ($pre_receive_hook_response->rejection_message === null) {
                    return Result::ok(null);
                }
                return Result::err(Fault::fromMessage($pre_receive_hook_response->rejection_message));
            }
        );
    }
}
