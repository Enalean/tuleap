<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Admin\ProjectCreation;

use Feedback;
use Project_CustomDescription_CustomDescriptionDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldAdminPresenterBuilder;

class ProjectsFieldDescriptionUpdater
{
    /**
     * @var Project_CustomDescription_CustomDescriptionDao
     */
    private $custom_description_dao;
    /**
     * @var \ConfigDao
     */
    private $config_dao;

    public function __construct(
        Project_CustomDescription_CustomDescriptionDao $custom_description_dao,
        \ConfigDao $config_dao
    ) {
        $this->custom_description_dao = $custom_description_dao;
        $this->config_dao             = $config_dao;
    }

    /**
     * @throws \DataAccessQueryException
     */
    public function updateDescription(?string $make_required_desc_id, ?string $remove_required_desc_id, BaseLayout $layout): void
    {
        if ($make_required_desc_id) {
            $this->updateRequiredDescription($make_required_desc_id, true, $layout);
        }

        if ($remove_required_desc_id) {
            $this->updateRequiredDescription($remove_required_desc_id, false, $layout);
        }
    }

    /**
     * @throws \DataAccessQueryException
     */
    private function updateRequiredDescription(string $id, bool $required, BaseLayout $layout): void
    {
        if ($id === DescriptionFieldAdminPresenterBuilder::SHORT_DESCRIPTION_FIELD_ID) {
            $this->config_dao->save("enable_not_mandatory_description", ! $required);
        } else {
            $this->custom_description_dao->updateRequiredCustomDescription($required, (int) $id);
        }
        $layout->addFeedback(Feedback::INFO, _('Project field has been successfully updated.'));
        $layout->redirect('/admin/project-creation/fields');
    }
}
