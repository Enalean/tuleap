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

use ConfigDao;
use Mockery;
use PHPUnit\Framework\TestCase;
use Project_CustomDescription_CustomDescriptionDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldAdminPresenterBuilder;

class ProjectsFieldDescriptionUpdaterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var ProjectsFieldDescriptionUpdater
     */
    private $updater;
    /**
     * @var ConfigDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $config_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project_CustomDescription_CustomDescriptionDao
     */
    private $custom_description_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->custom_description_dao = Mockery::mock(Project_CustomDescription_CustomDescriptionDao::class);
        $this->config_dao             = Mockery::mock(ConfigDao::class);

        $this->updater = new ProjectsFieldDescriptionUpdater(
            $this->custom_description_dao,
            $this->config_dao
        );
    }

    public function testItMakesCustomFieldDescriptionOptional(): void
    {
        $layout = \Mockery::mock(BaseLayout::class);

        $this->custom_description_dao->shouldReceive('updateRequiredCustomDescription')->withArgs([true, 1])->once();
        $layout->shouldReceive('addFeedback')->once()->withArgs([\Feedback::INFO, Mockery::any()]);
        $layout->shouldReceive('redirect')->once();

        $this->updater->updateDescription("1", null, $layout);
    }

    public function testItMakesCustomFieldDescriptionRequired(): void
    {
        $layout = \Mockery::mock(BaseLayout::class);

        $this->custom_description_dao->shouldReceive('updateRequiredCustomDescription')->withArgs([false, 1])->once();
        $layout->shouldReceive('addFeedback')->once()->withArgs([\Feedback::INFO, Mockery::any()]);
        $layout->shouldReceive('redirect')->once();

        $this->updater->updateDescription(null, "1", $layout);
    }

    public function testItProjectDescriptionFieldOptional(): void
    {
        $layout = \Mockery::mock(BaseLayout::class);

        $this->config_dao->shouldReceive('save')->withArgs(['enable_not_mandatory_description', false])->once();
        $layout->shouldReceive('addFeedback')->once()->withArgs([\Feedback::INFO, Mockery::any()]);
        $layout->shouldReceive('redirect')->once();

        $this->updater->updateDescription(
            DescriptionFieldAdminPresenterBuilder::SHORT_DESCRIPTION_FIELD_ID,
            null,
            $layout
        );
    }

    public function testItProjectDescriptionFieldRequired(): void
    {
        $layout = \Mockery::mock(BaseLayout::class);

        $this->config_dao->shouldReceive('save')->withArgs(['enable_not_mandatory_description', true])->once();
        $layout->shouldReceive('addFeedback')->once()->withArgs([\Feedback::INFO, Mockery::any()]);
        $layout->shouldReceive('redirect')->once();

        $this->updater->updateDescription(
            null,
            DescriptionFieldAdminPresenterBuilder::SHORT_DESCRIPTION_FIELD_ID,
            $layout
        );
    }
}
