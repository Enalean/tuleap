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

namespace Tuleap\Git\Hook\PreReceive;

use ForgeConfig;
use GitRepositoryFactory;
use Tuleap\WebAssembly\WASMCaller;

final class PreReceiveAnalyzeAction
{
    public function __construct(private GitRepositoryFactory $git_repository_factory, private WASMCaller $wasm_caller)
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
     *
     * @throws PreReceiveRepositoryNotFoundException
     * @throws PreReceiveWasmNotFoundException
     */
    public function preReceiveAnalyse(string $repository_id, array $pre_receive_args): string
    {
        $repository = $this->git_repository_factory->getRepositoryById((int) $repository_id);
        if ($repository === null) {
            throw new PreReceiveRepositoryNotFoundException();
        }

        $wasm_path = $this->getPossibleCustomPreReceiveHookPath($repository);
        if (! is_file($wasm_path)) {
            throw new PreReceiveWasmNotFoundException();
        }

        $hook_data = new PreReceiveHookData();
        $i         = 0;
        while ($i <= (count($pre_receive_args) - 3)) {
            $hook_data->addNewRev($pre_receive_args[$i + 2], new PreReceiveHookUpdatedReference($pre_receive_args[$i], $pre_receive_args[$i + 1]));
            $i += 3;
        }

        $json_in = json_encode($hook_data, JSON_THROW_ON_ERROR);

        return $this->wasm_caller->call($wasm_path, $json_in);
    }
}
