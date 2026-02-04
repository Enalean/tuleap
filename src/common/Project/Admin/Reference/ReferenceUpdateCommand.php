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
use Tuleap\Reference\CrossReferencesDao;

final readonly class ReferenceUpdateCommand
{
    public function __construct(
        private ReferenceManager $reference_manager,
        private CrossReferencesDao $cross_reference_dao,
    ) {
    }

    public function updateReference(
        Reference $existing_ref,
        HTTPRequest $request,
        bool $is_super_user,
        bool $force,
    ): bool {
        $is_used = (bool) ($request->get('is_used') ?? false);

        if ($this->isASystemReference($existing_ref)) {
            $this->updateSystemReference($existing_ref, $is_used);
            return true;
        }

        $old_keyword = (string) $existing_ref->getKeyword();
        $new_keyword = (string) $request->get('keyword');
        $updated_ref = $this->updateProjectReference($request, $is_super_user, $existing_ref, $is_used, $new_keyword);

        $result = $this->reference_manager->updateReference($updated_ref, $force);

        if ($result && $old_keyword !== $new_keyword) {
            $this->updateCrossReferences($old_keyword, $new_keyword, (int) $existing_ref->getGroupId());
        }

        return $result;
    }

    private function isASystemReference(Reference $ref): bool
    {
        return ($ref->isSystemReference() && $ref->getGroupId() !== \Project::DEFAULT_TEMPLATE_PROJECT_ID)
            || $ref->getServiceShortName() !== '';
    }

    private function resolveServiceShortName(string $new_service_shortname, bool $is_super_user): string
    {
        if (! $is_super_user || (int) $new_service_shortname ===  Service::NONE) {
            return '';
        }
        return $new_service_shortname;
    }

    private function updateCrossReferences(string $old, string $new, int $group_id): void
    {
        $this->cross_reference_dao->updateTargetKeyword($old, $new, $group_id);
        $this->cross_reference_dao->updateSourceKeyword($old, $new, $group_id);
    }

    private function updateSystemReference(Reference $existing_ref, bool $is_used): void
    {
        if ((bool) $existing_ref->isActive() !== $is_used) {
            $this->reference_manager->updateIsActive($existing_ref, $is_used);
        }
    }

    private function updateProjectReference(HTTPRequest $request, bool $is_super_user, Reference $existing_ref, bool $is_used, string $new_keyword): Reference
    {
        $service_short_name = $this->resolveServiceShortName((string) $request->get('service_short_name'), $is_super_user);
        return new Reference(
            $existing_ref->getId(),
            $new_keyword,
            (string) $request->get('description'),
            (string) $request->get('link'),
            $existing_ref->getScope(),
            $service_short_name,
            (string) $request->get('nature'),
            $is_used,
            $existing_ref->getGroupId()
        );
    }
}
