<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference\Browse;

use Tuleap\Event\Dispatchable;

class ExternalSystemReferencePresentersCollector implements Dispatchable
{
    public const NAME = 'externalSystemReferencePresentersCollector';

    /**
     * @var ExternalSystemReferencePresenter[]
     */
    private $external_system_reference_presenters = [];

    /**
     * @return ExternalSystemReferencePresenter[]
     */
    public function getExternalSystemReferencePresenters(): array
    {
        return $this->external_system_reference_presenters;
    }

    public function add(ExternalSystemReferencePresenter $presenter): void
    {
        $this->external_system_reference_presenters[] = $presenter;
    }
}
