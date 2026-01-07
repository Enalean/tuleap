<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference;

use Reference;
use ReferenceManager;
use Service;
use Tuleap\HTTPRequest;

final readonly class ReferenceCreateCommand
{
    public function __construct(
        private ReferenceManager $reference_manager,
    ) {
    }

    public function createReference(
        HTTPRequest $request,
        bool $is_super_user,
        bool $force,
    ): bool {
        $service_short_name = $this->resolveServiceShortName(
            (string) $request->get('service_short_name'),
            $is_super_user
        );

        $ref = new Reference(
            0,
            (string) $request->get('keyword'),
            (string) $request->get('description'),
            (string) $request->get('link'),
            (string) $request->get('scope'),
            $service_short_name,
            (string) $request->get('nature'),
            (bool) $request->get('is_used'),
            (int) $request->get('group_id')
        );

        if ($this->isGlobalSystemReference($ref)) {
            return $this->reference_manager->createSystemReference($ref, $force);
        }

        return (bool) $this->reference_manager->createReference($ref, $force);
    }

    private function isGlobalSystemReference(Reference $ref): bool
    {
        return $ref->getGroupId() === \Project::DEFAULT_TEMPLATE_PROJECT_ID && $ref->isSystemReference();
    }

    private function resolveServiceShortName(string $new_service_shortname, bool $is_super_user): string
    {
        if (! $is_super_user || (int) $new_service_shortname === Service::NONE) {
            return '';
        }
        return $new_service_shortname;
    }
}
