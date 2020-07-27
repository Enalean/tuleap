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

namespace Tuleap\TestPlan\TestDefinition;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TemplateRendererFactory;
use Tracker;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Templating\TemplateCache;
use Tuleap\Tracker\TrackerColor;

class RedirectParameterInjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private $injector;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var mixed
     */
    private $response;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact
     */
    private $backlog_item;

    protected function setUp(): void
    {
        $this->artifact_factory = Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->response         = $GLOBALS['Response'];

        $this->backlog_item = Mockery::mock(\Tracker_Artifact::class);
        $this->user         = Mockery::mock(\PFUser::class);

        $template_cache            = \Mockery::mock(TemplateCache::class);
        $template_cache->shouldReceive('getPath')->andReturnNull();
        $template_renderer_factory = new TemplateRendererFactory($template_cache);

        $this->injector = new RedirectParameterInjector(
            $this->artifact_factory,
            $this->response,
            $template_renderer_factory->getRenderer(__DIR__ . '/../../../templates/'),
        );
    }

    public function testItDoesNotInjectAnythingIfThereIsNoBacklogItemIdInTheRequest(): void
    {
        $request  = new \Codendi_Request([], \Mockery::spy(\ProjectManager::class));
        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfThereIsNoMilestoneIdInTheRequest(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => "123",
            ],
            \Mockery::spy(\ProjectManager::class)
        );
        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfTheBacklogItemIsNotReadableByUser(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ],
            \Mockery::spy(\ProjectManager::class)
        );
        $request->setCurrentUser($this->user);

        $redirect = new \Tracker_Artifact_Redirect();

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, "123")
            ->once()
            ->andReturnNull();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testInjectAndInformUserAboutBacklogItemBeingCovered(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ],
            \Mockery::spy(\ProjectManager::class)
        );
        $request->setCurrentUser($this->user);

        $redirect = new \Tracker_Artifact_Redirect();

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, "123")
            ->once()
            ->andReturn($this->backlog_item);
        //$this->backlog_item->shouldReceive('getXRefAndTitle')->andReturn('story #123 - My story');
        $this->backlog_item->shouldReceive('getUri')->andReturn('/plugins/tracker/?aid=123');
        $this->backlog_item->shouldReceive('getTitle')->andReturn('My story');
        $this->backlog_item->shouldReceive('getXref')->andReturn('story #123');
        $backlog_item_tracker = Mockery::mock(Tracker::class);
        $backlog_item_tracker->shouldReceive('getColor')->andReturn(TrackerColor::default());
        $this->backlog_item->shouldReceive('getTracker')->andReturn($backlog_item_tracker);


        $this->response
            ->shouldReceive('addFeedback')
            ->with(
                \Feedback::INFO,
                Mockery::on(
                    static function (string $content_to_display): bool {
                        return strpos($content_to_display, 'My story') !== false &&
                               strpos($content_to_display, 'story #123') !== false;
                    }
                ),
                CODENDI_PURIFIER_FULL
            )
            ->once();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        $this->assertEquals(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42"
            ],
            $redirect->query_parameters
        );
    }
}
