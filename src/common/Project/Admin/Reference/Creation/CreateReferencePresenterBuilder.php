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

namespace Tuleap\Project\Admin\Reference\Creation;

use Tuleap\Project\Service\ServiceDao;
use Tuleap\Reference\Nature;

final readonly class CreateReferencePresenterBuilder
{
    public function __construct(private ServiceDao $service_dao)
    {
    }

    /**
     * @param Nature[] $nature_list
     */
    public function buildReferencePresenter(int $project_id, array $nature_list, bool $is_super_user_in_default_template, string $url, \CSRFSynchronizerToken $csrf_token, \PFUser $user): CreateReferencePresenter
    {
        $service_list = [];

        $references_nature = [];
        foreach ($nature_list as $nature_key => $nature_desc) {
            if ($nature_desc->user_can_create_ref_with_nature) {
                $references_nature[] = new NatureReferencePresenter($nature_key, $nature_desc);
            }
        }


        if ($is_super_user_in_default_template) {
            $row   = $this->service_dao->searchById(100);
            $label = $row['label'];
            if ($label === 'service_' . $row['short_name'] . '_lbl_key') {
                $label = $GLOBALS['Language']->getOverridableText('project_admin_editservice', $label);
            }
            $service_list[] = new ServiceReferencePresenter($row['short_name'], $label);
        }

        return new CreateReferencePresenter(
            $project_id,
            $references_nature,
            $is_super_user_in_default_template,
            $service_list,
            $url,
            $csrf_token,
            $user->getShortLocale()
        );
    }
}
