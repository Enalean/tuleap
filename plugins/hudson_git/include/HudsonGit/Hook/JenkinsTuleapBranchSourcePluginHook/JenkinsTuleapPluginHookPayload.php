<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use GitRepository;
use Tuleap\Webhook\Payload;

class JenkinsTuleapPluginHookPayload implements Payload
{

    /**
     * @var array
     */
    private $payload;

    public function __construct(GitRepository $git_repository, string $refname)
    {
        $this->payload = $this->buildPayload($git_repository, $refname);
    }


    private function buildPayload(GitRepository $git_repository, string $refname): array
    {
        $branch_name = str_replace('refs/heads/', '', $refname);
        return [
            'tuleapProjectId'      => (string) $git_repository->getProjectId(),
            'repositoryName' => $git_repository->getName(),
            'branchName'     => $branch_name
        ];
    }

    public function getPayload()
    {
        return $this->payload;
    }
}
