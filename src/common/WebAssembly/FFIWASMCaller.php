<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\WebAssembly;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

/**
 * This class is preloaded, you need to restart PHP FPM to have changes made to it taken into account
 */
final class FFIWASMCaller implements WASMCaller
{
    /**
     * @var (\FFI&FFIWASMCallerStub)|null
     */
    private static ?\FFI $ffi = null;

    private const string RESPONSE_TYPE_NAME = 'wasm_response_total';
    private const string RESPONSE_TYPE_HELP = 'Total number of wasm response received';

    private const string EXEC_TIME_NAME            = 'wasm_module_duration_seconds';
    private const string EXEC_TIME_HELP            = 'Execution time of the WebAssembly module in seconds';
    private const array EXEC_TIME_BUCKETS          = [0.00025, 0.0005, 0.001, 0.002, 0.003, 0.004, 0.005, 0.006, 0.007, 0.008, 0.009, 0.01];
    private const string EXEC_TIME_FULL_NAME       = 'wasm_full_duration_seconds';
    private const string EXEC_TIME_FULL_HELP       = 'Execution time of the WebAssembly module in seconds, including the FFI back and forth, setting up Wamstime, executing the module...';
    private const array EXEC_TIME_FULL_BUCKETS     = [0.02, 0.03, 0.04, 0.05, 0.06, 0.07, 0.08, 0.09, 0.10];
    private const string MEMORY_CONSUMPTION_NAME   = 'wasm_memory_usage_bytes';
    private const string MEMORY_CONSUMPTION_HELP   = 'Memory consumed by the git WebAssembly module in bytes';
    private const array MEMORY_CONSUMPTION_BUCKETS = [1114112, 2228224, 3342336, 4194304];

    public function __construct(
        private readonly WASMCacheConfigurationBuilder $cache_configuration_builder,
        private readonly TreeMapper $mapper,
        private readonly Prometheus $prometheus,
        private readonly string $caller_name,
    ) {
    }

    #[\Override]
    public function call(string $wasm_path, string $module_input, WASMCallerRuntimeSettings $runtime_settings): Option
    {
        $config = [
            'wasm_module_path' => $wasm_path,
            'mount_points'     => $runtime_settings->mount_points,
            'limits'           => $runtime_settings->limits,
        ];

        $config_with_cache = $this->cache_configuration_builder->buildCacheConfiguration()
            ->mapOr(
                function (mixed $cache) use ($config): array {
                    $config['cache'] = $cache;
                    return $config;
                },
                $config
            );

        $config_json = json_encode($config_with_cache, JSON_THROW_ON_ERROR);

        $start_time = microtime(true);

        $output     = self::getFFIModule()->callWasmModule($config_json, $module_input);
        $output_php = \FFI::string($output);
        self::getFFIModule()->freeCallWasmModuleOutput($output);

        $end_time = microtime(true);

        $this->prometheus->histogram(
            self::EXEC_TIME_FULL_NAME,
            self::EXEC_TIME_FULL_HELP,
            ($end_time - $start_time),
            ['caller_name' => $this->caller_name],
            self::EXEC_TIME_FULL_BUCKETS
        );

        try {
            $wasm_response = $this->mapper->map(
                WASMInternalErrorResponse::class . '|' . WASMUserCodeErrorResponse::class . '|' . WASMValidResponse::class,
                Source::json($output_php),
            );
        } catch (MappingError | \RuntimeException $e) {
            throw WASMExecutionException::malformedResponse($e);
        }

        $value = match ($wasm_response::class) {
            WASMInternalErrorResponse::class => $this->processInternalErrorResponse($wasm_response),
            WASMUserCodeErrorResponse::class => $this->processUserCodeErrorResponse($wasm_response),
            WASMValidResponse::class         => $this->processValidResponse($wasm_response),
        };

        return Option::fromValue($value);
    }

    /**
     * @psalm-return never
     * @throws WASMExecutionException
     */
    private function processInternalErrorResponse(WASMInternalErrorResponse $wasm_response): never
    {
        $this->prometheus->increment(self::RESPONSE_TYPE_NAME, self::RESPONSE_TYPE_HELP, [
            'type'        => 'InternalErrorResponse',
            'caller_name' => $this->caller_name,
        ]);
        throw WASMExecutionException::internalError($wasm_response);
    }

    /**
     * @psalm-return Err<Fault>
     */
    private function processUserCodeErrorResponse(WASMUserCodeErrorResponse $wasm_response): Err
    {
        $this->prometheus->increment(self::RESPONSE_TYPE_NAME, self::RESPONSE_TYPE_HELP, [
            'type'        => 'UserCodeErrorResponse',
            'caller_name' => $this->caller_name,
        ]);
        $this->processStats($wasm_response->stats);
        return Result::err(Fault::fromMessage($wasm_response->user_error));
    }

    /**
     * @psalm-return Ok<String>
     */
    private function processValidResponse(WASMValidResponse $wasm_response): Ok
    {
        $this->prometheus->increment(self::RESPONSE_TYPE_NAME, self::RESPONSE_TYPE_HELP, [
            'type'        => 'ValidResponse',
            'caller_name' => $this->caller_name,
        ]);
        $this->processStats($wasm_response->stats);
        return Result::ok($wasm_response->data);
    }

    private function processStats(WASMStatistics $wasm_stats): void
    {
        $this->prometheus->histogram(
            self::EXEC_TIME_NAME,
            self::EXEC_TIME_HELP,
            $wasm_stats->exec_time_as_seconds,
            ['caller_name' => $this->caller_name],
            self::EXEC_TIME_BUCKETS
        );

        $this->prometheus->histogram(
            self::MEMORY_CONSUMPTION_NAME,
            self::MEMORY_CONSUMPTION_HELP,
            $wasm_stats->memory_in_bytes,
            ['caller_name' => $this->caller_name],
            self::MEMORY_CONSUMPTION_BUCKETS
        );
    }

    /**
     * @return \FFI&FFIWASMCallerStub $ffi
     */
    private static function getFFIModule(): \FFI
    {
        if (self::$ffi !== null) {
            return self::$ffi;
        }
        $ffi       = \FFI::scope('WASMTIME_WRAPPER');
        self::$ffi = $ffi;
        return $ffi;
    }
}
