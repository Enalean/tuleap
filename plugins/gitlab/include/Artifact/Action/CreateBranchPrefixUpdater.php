<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Artifact\Action;

use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationNotFoundException;

final class CreateBranchPrefixUpdater
{
    private const FAKE_BRANCH_NAME = 'branch_name';

    public function __construct(
        private GitlabRepositoryIntegrationFactory $integration_factory,
        private SaveIntegrationBranchPrefix $branch_prefix_saver,
    ) {
    }

    /**
     * @throws GitlabRepositoryIntegrationNotFoundException
     * @throws InvalidBranchNameException
     */
    public function updateBranchPrefix(int $integration_id, string $prefix): void
    {
        $gitlab_repository = $this->integration_factory->getIntegrationById($integration_id);
        if (! $gitlab_repository) {
            throw new GitlabRepositoryIntegrationNotFoundException($integration_id);
        }

        BranchName::fromBranchNameShortHand($prefix . self::FAKE_BRANCH_NAME);

        $this->branch_prefix_saver->setCreateBranchPrefixForIntegration(
            $integration_id,
            $prefix
        );
    }
}
