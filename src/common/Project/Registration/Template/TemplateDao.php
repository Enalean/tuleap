<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\DB\DataAccessObject;

class TemplateDao extends DataAccessObject
{
    public function saveTemplate(\Project $project, string $template_name): void
    {
        $this->getDB()->run(
            'INSERT INTO project_template_xml(id, template_name) VALUES (?, ?)',
            $project->getID(),
            $template_name,
        );
    }

    /**
     * @return string|false
     */
    public function getTemplateForProject(\Project $project)
    {
        return $this->getDB()->single('SELECT template_name FROM project_template_xml WHERE id = ?', [$project->getID()]);
    }
}
