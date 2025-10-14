<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Widget\ProjectMembers;

use Widget;

class ProjectMembers extends Widget
{
    public const string NAME = 'projectmembers';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    public function getTitle()
    {
        return _('Project Team');
    }

    #[\Override]
    public function getDescription()
    {
        return _('Lists the project members.');
    }

    #[\Override]
    public function getIcon()
    {
        return 'fa-users';
    }

    #[\Override]
    public function getContent(): string
    {
        $renderer = \TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../templates/widgets'
        );

        $request = \HTTPRequest::instance();
        $project = $request->getProject();

        $builder        = new AdministratorPresenterBuilder(
            new \UGroupUserDao(),
            \UserManager::instance(),
            \UserHelper::instance(),
            \EventManager::instance()
        );
        $administrators = $builder->build($project);

        return $renderer->renderToString(
            'project-members',
            new ProjectMembersPresenter($project, $administrators)
        );
    }

    #[\Override]
    public function exportAsXML(): \SimpleXMLElement
    {
        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        return $widget;
    }
}
