<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference\Browse;

use CSRFSynchronizerToken;

final readonly class ReferencePatternPresenterBuilder
{
    public function __construct(private \EventManager $event_manager, private \Tuleap\Reference\NatureCollection $nature_collection)
    {
    }

    public function buildProjectReference(\Reference $reference, bool $is_template_project, string $delete_reference_label): ProjectReferencePatternPresenter
    {
        $can_be_deleted    = $this->canReferenceBeDeleted($reference, $is_template_project);
        $description       = $reference->getResolvedDescription();
        $nature_desc       = $this->getNature($reference);
        $edit_url          = '/project/admin/reference.php?view=edit&group_id=' . $reference->getGroupId() . '&reference_id=' . $reference->getId();
        $keyword           = $this->getKeyword($is_template_project, $reference);
        $is_enabled        = $reference->isActive();
        $scope             = $this->getScope($is_template_project, $reference);
        $delete_url        = '/project/admin/reference.php?group_id=' . $reference->getGroupId() . '&reference_id=' . $reference->getId() . '&action=do_delete';
        $service_shortname = $reference->getServiceShortName();
        $csrf_token        = new CSRFSynchronizerToken('/project/admin/reference.php?group_id=' . $reference->getGroupId());

        return new ProjectReferencePatternPresenter(
            $keyword,
            $description,
            $nature_desc,
            $is_enabled,
            $edit_url,
            $delete_url,
            $scope,
            $can_be_deleted,
            $service_shortname,
            $csrf_token,
            $delete_reference_label
        );
    }

    private function canReferenceBeDeleted(\Reference $reference, bool $is_template_project): bool
    {
        $can_be_deleted = ($reference->getScope() !== 'S') || $is_template_project;
        $this->event_manager->processEvent(
            \Event::GET_REFERENCE_ADMIN_CAPABILITIES,
            [
                'reference'      => $reference,
                'can_be_deleted' => &$can_be_deleted,
            ]
        );

        return $can_be_deleted;
    }

    private function getNature(\Reference $reference): string
    {
        $available_nature = $this->nature_collection->getNatureFromIdentifier($reference->getNature());
        if ($available_nature) {
            $nature_desc = $available_nature->label;
        } else {
            $nature_desc = $reference->getNature();
        }
        return $nature_desc;
    }

    private function getKeyword(bool $is_template_project, \Reference $reference): string
    {
        if ($is_template_project) {
            return (string) $reference->getId();
        }
        return $reference->getKeyword();
    }

    private function getScope(bool $is_template_project, \Reference $reference): string
    {
        if ($is_template_project && $reference->getScope() === 'P') {
            return _('Project');
        }

        return _('System');
    }
}
