<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\DB;

use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;

final class ThereIsAnOngoingTransactionChecker extends DataAccessObject implements CheckThereIsAnOngoingTransaction
{
    #[FeatureFlagConfigKey('Reject actions that are going to be processed in a different context while being in a DB transaction')]
    #[ConfigKeyInt(0)]
    public const FEATURE_FLAG = 'check_actions_context_in_transaction';

    #[\Override]
    public function checkNoOngoingTransaction(): void
    {
        $feature_flag = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG);

        if ((int) $feature_flag !== 1) {
            return;
        }

        if ($this->getDB()->inTransaction()) {
            throw new \RuntimeException('You should not start something that will be processed in a different context while being in a transaction');
        }
    }
}
