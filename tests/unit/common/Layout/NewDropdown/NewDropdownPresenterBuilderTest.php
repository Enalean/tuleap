<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Layout\NewDropdown;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectCreationData;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\ProjectRegistrationErrorsCollection;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class NewDropdownPresenterBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private $event_dispatcher;
    private PFUser $user;
    private \Project $project;

    private ProjectRegistrationChecker $project_registration_checker_with_errors;
    private ProjectRegistrationChecker $project_registration_checker_without_errors;

    protected function setUp(): void
    {
        $this->event_dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->project_registration_checker_with_errors = new class implements ProjectRegistrationChecker
        {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                $errors_collection = new ProjectRegistrationErrorsCollection();
                $errors_collection->addError(
                    new class extends RegistrationForbiddenException
                    {
                        public function getI18NMessage(): string
                        {
                            return '';
                        }
                    }
                );

                return $errors_collection;
            }
        };

        $this->project_registration_checker_without_errors = new class implements ProjectRegistrationChecker
        {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                $errors_collection = new ProjectRegistrationErrorsCollection();
                return $errors_collection;
            }
        };

        $this->user    = UserTestBuilder::anActiveUser()->build();
        $this->project = ProjectTestBuilder::aProject()->withPublicName('Smartoid')->build();

        \ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'ACME');
    }

    public function testNoSectionsWhenUserCannotCreateProject(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_with_errors
        );

        $presenter = $builder->getPresenter($this->user, null, null);

        self::assertFalse($presenter->has_sections);
    }

    public function testGlobalSectionWhenUserCanCreateProject(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_without_errors
        );

        $presenter = $builder->getPresenter($this->user, null, null);

        self::assertTrue($presenter->has_sections);
        self::assertCount(1, $presenter->sections);
        self::assertEquals('ACME', $presenter->sections[0]->label);
        self::assertEquals('/project/new', $presenter->sections[0]->links[0]->url);
    }

    public function testNoSectionsWhenUserCannotDoStuffInProject(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_with_errors
        );

        $this->event_dispatcher
            ->method('dispatch')
            ->willReturn(new NewDropdownProjectLinksCollector($this->user, $this->project, null));

        $presenter = $builder->getPresenter($this->user, $this->project, null);

        self::assertFalse($presenter->has_sections);
    }

    public function testProjectSectionWhenUserCanDoStuffInProject(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_with_errors
        );

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon', []));

        $this->event_dispatcher
            ->method('dispatch')
            ->willReturn($collector);

        $presenter = $builder->getPresenter($this->user, $this->project, null);

        self::assertTrue($presenter->has_sections);
        self::assertCount(1, $presenter->sections);
        self::assertEquals('Smartoid', $presenter->sections[0]->label);
        self::assertEquals('/url', $presenter->sections[0]->links[0]->url);
    }

    public function testBothSectionsWhenUserCanDoStuffInProjectAndCanCreateProject(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_without_errors
        );

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon', []));

        $this->event_dispatcher
            ->method('dispatch')
            ->willReturn($collector);

        $presenter = $builder->getPresenter($this->user, $this->project, null);

        self::assertTrue($presenter->has_sections);
        self::assertCount(2, $presenter->sections);
        self::assertEquals('Smartoid', $presenter->sections[0]->label);
        self::assertEquals('/url', $presenter->sections[0]->links[0]->url);
        self::assertEquals('ACME', $presenter->sections[1]->label);
        self::assertEquals('/project/new', $presenter->sections[1]->links[0]->url);
    }

    public function testItAddsACurrentContextSection(): void
    {
        $builder = new NewDropdownPresenterBuilder(
            $this->event_dispatcher,
            $this->project_registration_checker_without_errors
        );

        $collector = new NewDropdownProjectLinksCollector($this->user, $this->project, null);
        $collector->addCurrentProjectLink(new NewDropdownLinkPresenter('/url', 'label', 'icon', []));

        $this->event_dispatcher
            ->method('dispatch')
            ->willReturn($collector);

        $current_context_section = new NewDropdownLinkSectionPresenter("Current context", [
            new \Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter('/path/to/submit/story', 'New story', 'fa-plus', []),
        ]);

        $presenter = $builder->getPresenter($this->user, $this->project, $current_context_section);

        self::assertTrue($presenter->has_sections);
        self::assertCount(3, $presenter->sections);
        self::assertEquals('Current context', $presenter->sections[0]->label);
        self::assertEquals('/path/to/submit/story', $presenter->sections[0]->links[0]->url);
    }
}
