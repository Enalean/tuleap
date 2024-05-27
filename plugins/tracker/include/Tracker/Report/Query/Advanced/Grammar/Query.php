<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use ForgeConfig;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;

final readonly class Query
{
    #[FeatureFlagConfigKey('Enable TQL syntax for SELECT')]
    #[ConfigKeyHidden]
    #[ConfigKeyInt(0)]
    public const ENABLE_SELECT = 'enable_tql_select';

    public static function isSelectEnabled(): bool
    {
        return (int) ForgeConfig::getFeatureFlag(self::ENABLE_SELECT) === 1;
    }

    /**
     * @param list<Selectable> $select
     * @throws SyntaxError
     */
    public function __construct(
        private array $select,
        private Logical $condition,
    ) {
        if ($this->select !== [] && ! self::isSelectEnabled()) {
            throw new SyntaxError('SELECT syntax cannot be used', null, '', 0, 0, 0);
        }
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function getCondition(): Logical
    {
        return $this->condition;
    }
}
