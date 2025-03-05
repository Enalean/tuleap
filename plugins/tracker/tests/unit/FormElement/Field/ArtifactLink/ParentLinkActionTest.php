<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParentLinkActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ParentLinkAction
     */
    private $action;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $parent_artifact;

    /**
     * @var Artifact&Mockery\MockInterface
     */
    private $another_parent_artifact;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $artifact_link_field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->action = new ParentLinkAction(
            $this->artifact_factory
        );

        $this->artifact = Mockery::mock(Artifact::class)->shouldReceive('getId')->andReturn(101)->getMock();

        $this->parent_artifact         = Mockery::mock(Artifact::class);
        $this->another_parent_artifact = Mockery::mock(Artifact::class);
        $this->user                    = Mockery::mock(PFUser::class);

        $this->artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class)
            ->shouldReceive('getId')
            ->andReturn(587)
            ->getMock();
    }

    public function testItReturnsTrueIfAtLeastOneLinkIsDone(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011', '1012'],
            ],
        ];

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($this->user)
            ->andReturn($this->artifact_link_field);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(1011)
            ->andReturn($this->parent_artifact);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(1012)
            ->andReturn($this->another_parent_artifact);

        $this->parent_artifact->shouldReceive('linkArtifact')
            ->once()
            ->with(101, $this->user, Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD)
            ->andReturnTrue();

        $this->another_parent_artifact->shouldReceive('linkArtifact')
            ->once()
            ->with(101, $this->user, Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD)
            ->andReturnFalse();

        $this->assertTrue(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfNoLinkCouldBeDone(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011', '1012'],
            ],
        ];

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($this->user)
            ->andReturn($this->artifact_link_field);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(1011)
            ->andReturn($this->parent_artifact);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(1012)
            ->andReturn($this->another_parent_artifact);

        $this->parent_artifact->shouldReceive('linkArtifact')
            ->once()
            ->with(101, $this->user, Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD)
            ->andReturnFalse();

        $this->another_parent_artifact->shouldReceive('linkArtifact')
            ->once()
            ->with(101, $this->user, Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD)
            ->andReturnFalse();

        $this->assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfNoArtifactLinkFieldForUser(): void
    {
        $fields_data = [
            587 => [
                'parent' => '1011',
            ],
        ];

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($this->user)
            ->andReturnNull();

        $this->parent_artifact->shouldNotReceive('linkArtifact');

        $this->assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfFieldsDataIsNotWellFormed(): void
    {
        $fields_data = [
            587 => [
                'whatever' => '1011',
            ],
        ];

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->with($this->user)
            ->andReturn($this->artifact_link_field);

        $this->parent_artifact->shouldNotReceive('linkArtifact');

        $this->assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );

        $fields_data = [
            'parent' => ['1011'],
        ];

        $this->assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfParentArtifactNotFound(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011'],
            ],
        ];

        $this->artifact->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($this->user)
            ->andReturn($this->artifact_link_field);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(1011)
            ->andReturnNull();

        $this->parent_artifact->shouldNotReceive('linkArtifact');

        $this->assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }
}
