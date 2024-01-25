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

namespace Tuleap\TrackerCCE\Administration;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\TrackerCCE\Logs\LogLinePresenter;

final class AdministrationPresenter
{
    /**
     * @param LogLinePresenter[] $logs
     */
    public function __construct(
        public readonly string $post_url,
        public readonly string $remove_url,
        public readonly string $activation_url,
        public readonly CSRFSynchronizerTokenPresenter $csrf,
        public readonly bool $has_uploaded_module,
        public readonly bool $is_activated,
        public readonly array $logs,
        public readonly ?string $explorer_url,
    ) {
    }
}
