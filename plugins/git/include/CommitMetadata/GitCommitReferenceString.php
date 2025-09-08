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

namespace Tuleap\Git\CommitMetadata;

use Tuleap\Git\Hook\DefaultBranchPush\CommitHash;

/**
 * @psalm-immutable
 */
final class GitCommitReferenceString implements \Tuleap\Reference\ReferenceString
{
    private function __construct(private string $reference)
    {
    }

    public static function fromRepositoryAndCommit(\GitRepository $repository, CommitHash $commit_hash): self
    {
        return new self(
            sprintf('%s #%s/%s', \Git::REFERENCE_KEYWORD, $repository->getFullName(), (string) $commit_hash)
        );
    }

    #[\Override]
    public function getStringReference(): string
    {
        return $this->reference;
    }
}
