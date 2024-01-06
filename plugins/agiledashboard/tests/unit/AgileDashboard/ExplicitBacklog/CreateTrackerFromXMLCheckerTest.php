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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Project;
use SimpleXMLElement;
use TrackerFromXmlException;
use Tuleap\Test\Builders\ProjectTestBuilder;

class CreateTrackerFromXMLCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CreateTrackerFromXMLChecker $checker;
    private Project $project;
    private ExplicitBacklogDao|\PHPUnit\Framework\MockObject\MockObject $explicit_backlog_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = $this->createMock(ExplicitBacklogDao::class);

        $this->checker = new CreateTrackerFromXMLChecker($this->explicit_backlog_dao);

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testItDoesNotThrowAnExceptionIfNoAddToTopBacklogTagProvided(): void
    {
        $xml = $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <tracker />
        ');

        $this->expectNotToPerformAssertions();

        $this->checker->checkTrackerCanBeCreatedInTrackerCreationContext(
            $this->project,
            $xml
        );
    }

    public function testItDoesNotThrowAnExceptionIfAddToTopBacklogTagProvidedAndProjectUsesExplicitTopBacklog()
    {
        $xml = $this->buildFullTrackerXML();

        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->checker->checkTrackerCanBeCreatedInTrackerCreationContext(
            $this->project,
            $xml
        );
    }

    public function testItThrowsAnExceptionIfAddToTopBacklogTagProvidedAndProjectDoesNotUseExplicitTopBacklog()
    {
        $xml = $this->buildFullTrackerXML();

        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->expectException(TrackerFromXmlException::class);

        $this->checker->checkTrackerCanBeCreatedInTrackerCreationContext(
            $this->project,
            $xml
        );
    }

    public function testItDoesNotThrowAnExceptionIfNoAddToTopBacklogTagProvidedInProjectImport()
    {
        $xml = $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project />
        ');

        $this->expectNotToPerformAssertions();

        $this->checker->checkTrackersCanBeCreatedInProjectImportContext($xml);
    }

    public function testItDoesNotThrowAnExceptionIfAddToTopBacklogTagProvidedAndImportedProjectWillUseExplicitTopBacklog()
    {
        $xml = $this->buildFullProjectXML();

        $this->expectNotToPerformAssertions();

        $this->checker->checkTrackersCanBeCreatedInProjectImportContext($xml);
    }

    public function testItThrowsAnExceptionIfAddToTopBacklogTagProvidedAndProjectWillNotUseExplicitTopBacklog()
    {
        $xml = $this->buildFullProjectXMLWithoutExplicit();

        $this->expectException(ProjectNotUsingExplicitBacklogException::class);

        $this->checker->checkTrackersCanBeCreatedInProjectImportContext($xml);
    }

    private function buildFullProjectXML(): SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <agiledashboard>
                    <admin>
                        <scrum>
                            <explicit_backlog is_used="1"/>
                        </scrum>
                    </admin>
                </agiledashboard>
                <tracker>
                    <workflow>
                        <field_id REF="F3699"/>
                        <is_used>1</is_used>
                        <transitions>
                          <transition>
                            <from_id REF="V2218"/>
                            <to_id REF="V2219"/>
                            <postactions>
                              <postaction_add_to_top_backlog/>
                            </postactions>
                            <conditions>
                              <condition type="perms">
                                <permissions>
                                  <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                                </permissions>
                              </condition>
                            </conditions>
                          </transition>
                        </transitions>
                      </workflow>
                </tracker>
            </project>
        ');

        return $xml;
    }

    private function buildFullProjectXMLWithoutExplicit(): SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <agiledashboard/>
                <tracker>
                    <workflow>
                        <field_id REF="F3699"/>
                        <is_used>1</is_used>
                        <transitions>
                          <transition>
                            <from_id REF="V2218"/>
                            <to_id REF="V2219"/>
                            <postactions>
                              <postaction_add_to_top_backlog/>
                            </postactions>
                            <conditions>
                              <condition type="perms">
                                <permissions>
                                  <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                                </permissions>
                              </condition>
                            </conditions>
                          </transition>
                        </transitions>
                      </workflow>
                </tracker>
            </project>
        ');

        return $xml;
    }

    private function buildFullTrackerXML(): SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <workflow>
                    <field_id REF="F3699"/>
                    <is_used>1</is_used>
                    <transitions>
                      <transition>
                        <from_id REF="V2218"/>
                        <to_id REF="V2219"/>
                        <postactions>
                          <postaction_add_to_top_backlog/>
                        </postactions>
                        <conditions>
                          <condition type="perms">
                            <permissions>
                              <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                            </permissions>
                          </condition>
                        </conditions>
                      </transition>
                    </transitions>
                  </workflow>
            </tracker>
        ');

        return $xml;
    }
}
