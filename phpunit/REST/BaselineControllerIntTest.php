<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tuleap\Baseline\Factory\ChangesetFactory;
use Tuleap\Baseline\Factory\MilestoneFactory;
use Tuleap\Baseline\Stub\BaselineRepositoryStub;
use Tuleap\Baseline\Stub\ChangesetRepositoryStub;
use Tuleap\Baseline\Stub\CurrentUserProviderStub;
use Tuleap\Baseline\Stub\FieldRepositoryStub;
use Tuleap\Baseline\Stub\FrozenClock;
use Tuleap\Baseline\Stub\MilestoneRepositoryStub;
use Tuleap\Baseline\Stub\PermissionsStub;
use Tuleap\Baseline\Support\DependenciesContext;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\I18NRestException;

class BaselineControllerIntTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var BaselineController */
    private $controller;

    /** @var FieldRepositoryStub */
    private $field_repository;

    /** @var MilestoneRepositoryStub */
    private $milestone_repository;

    /** @var ChangesetRepositoryStub */
    private $changeset_repository;

    /** @var BaselineRepositoryStub */
    private $baseline_repository;

    /** @var PermissionsStub */
    private $permissions;

    /** @var CurrentUserProviderStub */
    private $current_user_provider;

    /** @var FrozenClock */
    private $clock;

    /** @before */
    public function createContextWithStubs()
    {
        $context = new DependenciesContext();

        $this->field_repository = new FieldRepositoryStub();
        $context->setFieldRepository($this->field_repository);

        $this->milestone_repository = new MilestoneRepositoryStub();
        $context->setMilestoneRepository($this->milestone_repository);

        $this->changeset_repository = new ChangesetRepositoryStub();
        $context->setChangesetRepository($this->changeset_repository);

        $this->baseline_repository = new BaselineRepositoryStub();
        $context->setBaselineRepository($this->baseline_repository);

        $this->permissions = new PermissionsStub();
        $context->setPermissions($this->permissions);

        $this->current_user_provider = new CurrentUserProviderStub();
        $context->setCurrentUserProvider($this->current_user_provider);

        $this->clock = new FrozenClock();
        $context->setClock($this->clock);

        $this->controller = $context->getBaselineController();
    }

    public function testPost()
    {
        $this->permissions->permitAll();
        $milestone = MilestoneFactory::one()->id(2)->build();
        $this->milestone_repository->add($milestone);

        $this->controller->post('My first baseline', 2);

        $this->assertEquals(1, $this->baseline_repository->count());
        $baseline = $this->baseline_repository->findAny();
        $this->assertEquals('My first baseline', $baseline->getName());
        $this->assertEquals($milestone, $baseline->getMilestone());
        $this->assertEquals($this->current_user_provider->getUser(), $baseline->getAuthor());
        $this->assertEquals($this->clock->now(), $baseline->getCreationDate());
    }

    public function testGetByMilestoneIdAndDate()
    {
        $this->permissions->permitAll();
        $this->milestone_repository->add(
            MilestoneFactory::one()->id(3)->build()
        );

        $title_field       = $this->saveATitleField();
        $description_field = $this->saveADescriptionField();
        $status_field      = $this->saveAStatusField();

        $this->changeset_repository->setSingleChangesetForAllDates(
            ChangesetFactory::one()
                ->submittedOn(1555459100)
                ->textValue($title_field, 'My first baseline')
                ->textValue($description_field, 'Baseline details')
                ->firstListValue($status_field, 'On going')
                ->build()
        );

        $simplified_baseline = $this->controller->getByMilestoneIdAndDate(3, '2019-03-21');

        $this->assertNotNull($simplified_baseline);
        $this->assertEquals('My first baseline', $simplified_baseline->artifact_title);
        $this->assertEquals('Baseline details', $simplified_baseline->artifact_description);
        $this->assertEquals('On going', $simplified_baseline->artifact_status);
        $this->assertEquals(1555459100, $simplified_baseline->last_modification_date_before_baseline_date);
    }

    public function testGetByMilestoneIdAndDateThrowWhenUserCanNotViewArtifact()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);

        $this->permissions->denyAll();
        $this->milestone_repository->add(
            MilestoneFactory::one()->id(3)->build()
        );

        $this->changeset_repository->setSingleChangesetForAllDates(ChangesetFactory::one()->build());

        $this->controller->getByMilestoneIdAndDate(3, '2019-03-21');
    }

    public function testGetByMilestoneIdAndDateThrowsWhenGivenDateFormatIsInvalid()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->permissions->permitAll();

        $this->controller->getByMilestoneIdAndDate(3, 'invalid date format');
    }

    public function testGetByMilestoneIdAndDateThrowsWhenNotArtifactExistWithGivenId()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->permissions->permitAll();
        $this->milestone_repository->removeAll();

        $this->controller->getByMilestoneIdAndDate(3, '2019-03-21');
    }

    public function testGetByMilestoneIdAndDateReturnsEmptyWhenNoChangeset()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->permissions->permitAll();
        $this->milestone_repository->add(
            MilestoneFactory::one()->id(3)->build()
        );

        $this->changeset_repository->removeAll();

        $this->controller->getByMilestoneIdAndDate(3, '2019-03-21');
    }

    /**
     * @return Tracker_FormElement_Field_Text|MockInterface
     */
    private function saveATitleField()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->field_repository->setTitleForAllTrackers($field);
        return $field;
    }

    /**
     * @return Tracker_FormElement_Field_Text|MockInterface
     */
    private function saveADescriptionField()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->field_repository->setDescriptionForAllTrackers($field);
        return $field;
    }

    /**
     * @return Tracker_FormElement_Field_List|MockInterface
     */
    private function saveAStatusField()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->field_repository->setStatusForAllTrackers($field);
        return $field;
    }
}
