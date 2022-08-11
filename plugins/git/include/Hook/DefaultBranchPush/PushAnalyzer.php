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

namespace Tuleap\Git\Hook\DefaultBranchPush;

use Tuleap\Git\Hook\CommitHash;
use Tuleap\Git\Hook\PushDetails;
use Tuleap\Git\Hook\VerifyIsDefaultBranch;

final class PushAnalyzer
{
    public function __construct(private VerifyIsDefaultBranch $default_branch_verifier)
    {
    }

    public function analyzePush(PushDetails $details): ?DefaultBranchPushReceived
    {
        if (! $this->default_branch_verifier->isDefaultBranch($details->getRefname())) {
            return null;
        }
        $hashes = array_map(static fn(string $sha1) => CommitHash::fromString($sha1), $details->getRevisionList());
        return new DefaultBranchPushReceived($details->getRepository(), $details->getUser(), $hashes);
    }
}
