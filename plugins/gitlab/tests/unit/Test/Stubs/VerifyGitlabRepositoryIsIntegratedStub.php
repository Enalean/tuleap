<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Tuleap\Gitlab\Repository\VerifyGitlabRepositoryIsIntegrated;

final class VerifyGitlabRepositoryIsIntegratedStub implements VerifyGitlabRepositoryIsIntegrated
{
    private function __construct(private bool $return_value)
    {
    }

    #[\Override]
    public function isTheGitlabRepositoryAlreadyIntegratedInProject(
        int $project_id,
        int $gitlab_repository_id,
        string $http_path,
    ): bool {
        return $this->return_value;
    }

    public static function withAlwaysIntegrated(): self
    {
        return new self(true);
    }

    public static function withNeverIntegrated(): self
    {
        return new self(false);
    }
}
