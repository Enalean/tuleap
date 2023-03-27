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

use ForgeConfig;
use GitRepositoryFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\WebAssembly\WASMCaller;

final class PreReceiveAction
{
    public function __construct(private GitRepositoryFactory $git_repository_factory, private WASMCaller $wasm_caller, private LoggerInterface $logger)
    {
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
     * @psalm-return Ok<null>|Err<Fault>
     * @throws PreReceiveRepositoryNotFoundException
     */
    public function preReceiveExecute(string $repository_path): Ok|Err
    {
        if (ForgeConfig::getFeatureFlag(PreReceiveCommand::FEATURE_FLAG_KEY) !== '1') {
            return Result::ok(null);
        }

        $repository = $this->git_repository_factory->getFromFullPath($repository_path);
        if ($repository === null) {
            throw new PreReceiveRepositoryNotFoundException();
        }

        $wasm_path = $this->getPossibleCustomPreReceiveHookPath($repository);
        if (! is_file($wasm_path)) {
            return Result::ok(null);
        }

        $input_data = stream_get_contents(STDIN);

        $this->logger->debug("[pre-receive] Monitoring updated refs for: '$repository_path'");
        return PreReceiveHookData::fromRawStdinHook($input_data, $this->logger)
            ->andThen(
                /** @psalm-return Ok<null>|Err<Fault> */
                function (PreReceiveHookData $hook_result) use ($wasm_path): Ok|Err {
                    $json_in = json_encode($hook_result, JSON_THROW_ON_ERROR);
                    return $this->wasm_caller->call($wasm_path, $json_in)->mapOr(
                        /** @psalm-return Ok<null>|Err<Fault> */
                        function (string $wasm_data): Ok|Err {
                            return $this->processResponse($wasm_data);
                        },
                        Result::ok(null)
                    );
                }
            );
    }

    /** @psalm-return Ok<null>|Err<Fault> */
    private function processResponse(string $wasm_data): Ok|Err
    {
        try {
            $json = json_decode($wasm_data, true, 2, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return Result::err(Fault::fromThrowableWithMessage($exception, 'The JSON returned by the WASM module is invalid or malformed.'));
        }

        if (array_key_exists('internal_error', $json)) {
            $this->logger->error('[pre-receive] Internal error: ' . $json['internal_error']);
            throw new RuntimeException("An error occurred in wasmtime-wrapper-lib !");
        }

        if (array_key_exists('user_error', $json)) {
            return Result::err(Fault::fromMessage('Error: ' . $json['user_error']));
        }

        if (! array_key_exists('rejection_message', $json)) {
            return Result::err(Fault::fromMessage('The JSON returned by the WASM module does not contain a valid `rejection_message` key'));
        }

        if ($json['rejection_message'] === null) {
            return Result::ok(null);
        }

        if (! is_string($json['rejection_message'])) {
            return Result::err(Fault::fromMessage('Error: the rejection message returned by the WASM module is not a string.'));
        }

        return Result::err(Fault::fromMessage('Rejection message: ' . $json['rejection_message']));
    }
}
