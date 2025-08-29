<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Tests\Setup;

use Project;
use ProjectManager;
use TemplateSingleton;
use Tuleap\admin\ProjectEdit\ProjectEditDao;

final readonly class ArtidocFieldsPreparator
{
    public const string FIELDS_TEMPLATE_SHORTNAME = 'artidoc-fields';

    public function __construct(
        private ProjectManager $project_manager,
        private ProjectEditDao $project_edit_dao,
    ) {
    }

    public function setup(): void
    {
        $this->markArtidocFieldsProjectAsTemplate();
    }

    private function markArtidocFieldsProjectAsTemplate(): void
    {
        $artidoc_fields_template = $this->project_manager->getProjectByUnixName(self::FIELDS_TEMPLATE_SHORTNAME);
        if ($artidoc_fields_template) {
            $this->project_edit_dao->updateProjectStatusAndType(
                Project::STATUS_ACTIVE,
                TemplateSingleton::TEMPLATE,
                $artidoc_fields_template->getID()
            );
        }
    }
}
