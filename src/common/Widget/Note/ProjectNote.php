<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Widget\Note;

use Codendi_Request;
use Project;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Project\MappingRegistry;

class ProjectNote extends Note
{
    public const string NAME = 'projectnote';

    #[\Override]
    protected static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getDescription(): string
    {
        return _('Allow to write informations for users on your dashboards using Markdown');
    }

    #[\Override]
    public function create(Codendi_Request $request)
    {
        if ($this->owner_id === null) {
            $current_project = $request->getProject();
            if ($current_project && ! $current_project->isError()) {
                $this->setOwner($current_project->getID(), ProjectDashboardController::LEGACY_DASHBOARD_TYPE);
            } else {
                return false;
            }
        }

        return $this->createNote($request, $this->owner_id, $this->owner_type);
    }

    #[\Override]
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        return $this->dao->duplicate($new_project->getID(), $id);
    }

    #[\Override]
    public function exportAsXML(): \SimpleXMLElement
    {
        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        $preference = $widget->addChild('preference');
        $preference->addAttribute('name', 'note');

        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->title,
            ['name' => 'title']
        );
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->content,
            ['name' => 'content']
        );

        return $widget;
    }
}
