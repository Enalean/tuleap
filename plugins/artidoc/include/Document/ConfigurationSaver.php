<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use Override;
use Tuleap\Artidoc\Document\Field\SaveConfiguredFields;
use Tuleap\DB\DBTransactionExecutor;

final readonly class ConfigurationSaver implements SaveConfiguration
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private SaveConfiguredTracker $save_tracker,
        private SaveConfiguredFields $save_fields,
    ) {
    }

    #[Override]
    public function saveConfiguration(int $item_id, int $tracker_id, array $fields): void
    {
        $this->transaction_executor->execute(function () use ($item_id, $tracker_id, $fields) {
            $this->save_tracker->saveTracker($item_id, $tracker_id);
            $this->save_fields->saveFields($item_id, $fields);
        });
    }
}
