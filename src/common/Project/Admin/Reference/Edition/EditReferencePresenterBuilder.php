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

namespace Tuleap\Project\Admin\Reference\Edition;

use Tuleap\Project\Service\ServiceDao;
use Tuleap\Reference\Nature;

final class EditReferencePresenterBuilder
{
    public function __construct(private ServiceDao $service_dao, private CheckReferenceIsReadOnly $check_reference_is_readonly)
    {
    }

    /**
     * @param Nature[] $nature_list
     */
    public function buildReferencePresenter(int $project_id, array $nature_list, \PFUser $user, string $url, \CSRFSynchronizerToken $csrf_token, \Reference $reference): EditReferencePresenter
    {
        $is_reference_read_only = $this->check_reference_is_readonly->isReferenceReadOnly($reference);

        $references_nature = [];
        foreach ($nature_list as $nature_key => $nature_desc) {
            if ($nature_desc->user_can_create_ref_with_nature) {
                $references_nature[] = new EditNatureReferencePresenter($nature_key, $nature_desc, $reference->getNature() === $nature_key);
            }
        }

        $service_list  = [];
        $is_super_user = $user->isSuperUser();
        if ($is_super_user) {
            $results = $this->service_dao->searchByProjectId(\Project::DEFAULT_TEMPLATE_PROJECT_ID);
            foreach ($results as $row) {
                $label = $row['label'];

                if ($label === 'service_' . $row['short_name'] . '_lbl_key') {
                    $label = $GLOBALS['Language']->getOverridableText('project_admin_editservice', $label);
                }
                $service_list[] = new EditServiceReferencePresenter($row['short_name'], $label, $reference->getServiceShortName() === $row['short_name']);
            }
        }

        $is_in_default_template = $project_id === \Project::DEFAULT_TEMPLATE_PROJECT_ID;

        return new EditReferencePresenter(
            $project_id,
            $references_nature,
            $service_list,
            $url,
            $csrf_token,
            $user->getShortLocale(),
            $reference,
            $is_reference_read_only,
            $is_super_user,
            $is_in_default_template
        );
    }
}
