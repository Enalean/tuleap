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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;

final class JenkinsTuleapPluginHookTokenGeneratorDBStore implements JenkinsTuleapPluginHookTokenGenerator
{
    public function __construct(
        private JenkinsTuleapPluginHookTokenDAO $dao,
        private SplitTokenVerificationStringHasher $hasher,
        private SplitTokenFormatter $split_token_formatter,
        private \DateInterval $expiration_delay,
    ) {
    }

    #[\Override]
    public function generateTriggerToken(\DateTimeImmutable $now): ConcealedString
    {
        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $token_id = $this->dao->create(
            $this->hasher->computeHash($secret),
            $now->add($this->expiration_delay)->getTimestamp(),
        );

        return $this->split_token_formatter->getIdentifier(
            new SplitToken(
                $token_id,
                new SplitTokenVerificationString($secret->getString())
            )
        );
    }
}
