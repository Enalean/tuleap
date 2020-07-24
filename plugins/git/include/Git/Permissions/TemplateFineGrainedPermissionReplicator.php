<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use Project;

class DefaultFineGrainedPermissionReplicator
{
    /**
     * @var FineGrainedDao
     */
    private $fine_grained_dao;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $factory;

    /**
     * @var TemplateFineGrainedPermissionSaver
     */
    private $saver;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var PatternValidator
     */
    private $pattern_validator;

    public function __construct(
        FineGrainedDao $fine_grained_dao,
        DefaultFineGrainedPermissionFactory $factory,
        TemplateFineGrainedPermissionSaver $saver,
        RegexpFineGrainedEnabler $regexp_enabler,
        RegexpFineGrainedRetriever $regexp_retriever,
        PatternValidator $pattern_validator
    ) {
        $this->fine_grained_dao  = $fine_grained_dao;
        $this->factory           = $factory;
        $this->saver             = $saver;
        $this->regexp_enabler    = $regexp_enabler;
        $this->regexp_retriever  = $regexp_retriever;
        $this->pattern_validator = $pattern_validator;
    }

    public function replicate(
        Project $template_project,
        $new_project_id,
        array $ugroups_mapping
    ) {
        $this->replicateDefaultRegexpUsage($template_project, $new_project_id);

        $this->fine_grained_dao->duplicateDefaultFineGrainedPermissionsEnabled(
            $template_project->getId(),
            $new_project_id
        );

        $replicated_branch_permissions = $this->factory->mapBranchPermissionsForProject(
            $template_project,
            $new_project_id,
            $ugroups_mapping
        );

        $warnings = [];
        foreach ($replicated_branch_permissions as $permission) {
            if ($this->pattern_validator->isValidForDefault($template_project, $permission->getPattern(), false)) {
                $this->saver->saveBranchPermission($permission);
            } else {
                $warnings[] =  $permission->getPattern();
            }
        }

        $replicated_tag_permissions = $this->factory->mapTagPermissionsForProject(
            $template_project,
            $new_project_id,
            $ugroups_mapping
        );

        foreach ($replicated_tag_permissions as $permission) {
            if ($this->pattern_validator->isValidForDefault($template_project, $permission->getPattern(), false)) {
                $this->saver->saveTagPermission($permission);
            }
        }

        if (count($warnings) > 0) {
            $GLOBALS['Response']->addFeedback('warning', sprintf(dgettext('tuleap-git', 'Your platform does not allow regular expression usage, permissions %1$s not duplicated.'), implode(',', $warnings)));
        }
    }

    public function replicateDefaultRegexpUsage(Project $template_project, $new_project_id)
    {
        if ($this->regexp_retriever->areRegexpActivatedForDefault($template_project)) {
            $this->regexp_enabler->enableForTemplateWithProjectId($new_project_id);
        }
    }
}
