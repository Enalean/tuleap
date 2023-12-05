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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Config\ConfigDao;
use Project_CustomDescription_CustomDescriptionDao;
use Tuleap\Admin\ProjectCreation\ProjetFields\ProjectsFieldDescriptionUpdater;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldAdminPresenterBuilder;

class ProjectsFieldDescriptionUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private ProjectsFieldDescriptionUpdater $updater;
    private ConfigDao&MockObject $config_dao;
    private Project_CustomDescription_CustomDescriptionDao&MockObject $custom_description_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->custom_description_dao = $this->createMock(Project_CustomDescription_CustomDescriptionDao::class);
        $this->config_dao             = $this->createMock(ConfigDao::class);

        $this->updater = new ProjectsFieldDescriptionUpdater(
            $this->custom_description_dao,
            $this->config_dao
        );
    }

    public function testItMakesCustomFieldDescriptionOptional(): void
    {
        $layout = $this->createMock(BaseLayout::class);

        $this->custom_description_dao->expects(self::once())->method('updateRequiredCustomDescription')->with(true, 1);
        $layout->expects(self::once())->method('addFeedback')->with(\Feedback::INFO, self::anything());
        $layout->expects(self::once())->method('redirect');

        $this->updater->updateDescription("1", null, $layout);
    }

    public function testItMakesCustomFieldDescriptionRequired(): void
    {
        $layout = $this->createMock(BaseLayout::class);

        $this->custom_description_dao->expects(self::once())->method('updateRequiredCustomDescription')->with(false, 1);
        $layout->expects(self::once())->method('addFeedback')->with(\Feedback::INFO, self::anything());
        $layout->expects(self::once())->method('redirect');

        $this->updater->updateDescription(null, "1", $layout);
    }

    public function testItProjectDescriptionFieldOptional(): void
    {
        $layout = $this->createMock(BaseLayout::class);

        $this->config_dao->expects(self::once())->method('saveBool')->with('enable_not_mandatory_description', false);
        $layout->expects(self::once())->method('addFeedback')->with(\Feedback::INFO, self::anything());
        $layout->expects(self::once())->method('redirect');

        $this->updater->updateDescription(
            DescriptionFieldAdminPresenterBuilder::SHORT_DESCRIPTION_FIELD_ID,
            null,
            $layout
        );
    }

    public function testItProjectDescriptionFieldRequired(): void
    {
        $layout = $this->createMock(BaseLayout::class);

        $this->config_dao->expects(self::once())->method('saveBool')->with('enable_not_mandatory_description', true);
        $layout->expects(self::once())->method('addFeedback')->with(\Feedback::INFO, self::anything());
        $layout->expects(self::once())->method('redirect');

        $this->updater->updateDescription(
            null,
            DescriptionFieldAdminPresenterBuilder::SHORT_DESCRIPTION_FIELD_ID,
            $layout
        );
    }
}
