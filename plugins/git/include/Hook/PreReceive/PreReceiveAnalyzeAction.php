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

use Git_Exec;
use GitRepositoryFactory;

final class PreReceiveAnalyzeAction
{
    public function __construct(private GitRepositoryFactory $git_repository_factory, private WASMCaller $wasm_caller)
    {
    }

    /**
     * Analyze information related to a git object reference
     *
     * @throws PreReceiveRepositoryNotFoundException
     * @throws PreReceiveCannotRetrieveReferenceException
     */
    public function preReceiveAnalyse(string $repository_id, string $git_reference): string
    {
        $repository = $this->git_repository_factory->getRepositoryById((int) $repository_id);
        if ($repository === null) {
            throw new PreReceiveRepositoryNotFoundException();
        }

        $git_exec = Git_Exec::buildFromRepository($repository);

        try {
            $arr      = ["obj_type" => $git_exec->getObjectType($git_reference), "content" => $git_exec->catFile($git_reference)];
            $json_in  = json_encode($arr, JSON_THROW_ON_ERROR);
            $json_out = $this->wasm_caller->call($json_in);
        } catch (\Git_Command_Exception $e) {
            throw new PreReceiveCannotRetrieveReferenceException();
        }

        return $json_out;
    }
}
