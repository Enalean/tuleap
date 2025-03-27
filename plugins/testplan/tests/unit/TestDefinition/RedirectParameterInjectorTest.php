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

use PHPUnit\Framework\Attributes\DataProvider;
use TemplateRendererFactory;
use Tracker;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Templating\TemplateCache;
use Tuleap\Tracker\TrackerColor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RedirectParameterInjectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $backlog_item;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;

    private mixed $response;
    private RedirectParameterInjector $injector;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->response         = $GLOBALS['Response'];

        $this->backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->backlog_item->method('getId')->willReturn(123);
        $this->user = $this->createMock(\PFUser::class);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);
        $template_renderer_factory = new TemplateRendererFactory($template_cache);

        $this->injector = new RedirectParameterInjector(
            $this->artifact_factory,
            $this->response,
            $template_renderer_factory->getRenderer(__DIR__ . '/../../../../agiledashboard/templates/'),
        );
    }

    public function testItDoesNotInjectAnythingIfThereIsNoBacklogItemIdInTheRequest(): void
    {
        $request  = new \Codendi_Request([], $this->createMock(\ProjectManager::class));
        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfThereIsNoMilestoneIdInTheRequest(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => '123',
            ],
            $this->createMock(\ProjectManager::class)
        );
        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfTheBacklogItemIsNotReadableByUser(): void
    {
        $request = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ],
            $this->createMock(\ProjectManager::class)
        );
        $request->setCurrentUser($this->user);

        $redirect = new \Tracker_Artifact_Redirect();

        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->willReturnMap(
                [
                    [$this->user, '123', null],
                    [$this->user, '42', $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class)],
                ],
            );

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfTheMilestoneIsNotReadableByUser(): void
    {
        $request = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ],
            $this->createMock(\ProjectManager::class)
        );
        $request->setCurrentUser($this->user);

        $redirect = new \Tracker_Artifact_Redirect();

        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->willReturnMap(
                [
                    [$this->user, '42', null],
                    [$this->user, '123', $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class)],
                ],
            );

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        self::assertEquals([], $redirect->query_parameters);
    }

    /**
     * @param array<string,string> $request_parameters
     * @psalm-param callable(string):bool $has_expected_feedback_content
     */
    #[DataProvider('dataProviderInjectAndInformUserAboutBacklogItemBeingCovered')]
    public function testInjectAndInformUserAboutBacklogItemBeingCovered(array $request_parameters, callable $has_expected_feedback_content): void
    {
        $request = new \Codendi_Request(
            array_merge(
                [
                    'ttm_backlog_item_id' => '123',
                    'ttm_milestone_id' => '42',
                ],
                $request_parameters
            ),
            $this->createMock(\ProjectManager::class)
        );
        $request->setCurrentUser($this->user);

        $redirect = new \Tracker_Artifact_Redirect();

        $milestone = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->willReturnMap(
                [
                    [$this->user, '123', $this->backlog_item],
                    [$this->user, '42', $milestone],
                ],
            );

        $this->backlog_item->method('getUri')->willReturn('/plugins/tracker/?aid=123');
        $this->backlog_item->method('getTitle')->willReturn('My story');
        $this->backlog_item->method('getXref')->willReturn('story #123');
        $backlog_item_tracker = $this->createMock(Tracker::class);
        $backlog_item_tracker->method('getColor')->willReturn(TrackerColor::default());
        $this->backlog_item->method('getTracker')->willReturn($backlog_item_tracker);
        $milestone->method('getUri')->willReturn('/plugins/tracker/?aid=42');
        $milestone->method('getTitle')->willReturn('Some milestone');
        $milestone->method('getXref')->willReturn('rel #42');
        $milestone_tracker = $this->createMock(Tracker::class);
        $milestone_tracker->method('getColor')->willReturn(TrackerColor::default());
        $milestone->method('getTracker')->willReturn($milestone_tracker);

        $this->response
            ->expects($this->once())
            ->method('addFeedback')
            ->with(
                \Feedback::INFO,
                self::callback($has_expected_feedback_content),
                CODENDI_PURIFIER_FULL
            );

        $this->injector->injectAndInformUserAboutBacklogItemBeingCovered($request, $redirect);

        self::assertEquals(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ],
            $redirect->query_parameters
        );
    }

    public static function dataProviderInjectAndInformUserAboutBacklogItemBeingCovered(): array
    {
        return [
            'Add test definition' => [
                ['func' => 'new-artifact'],
                static function (string $content_to_display): bool {
                    return strpos($content_to_display, 'My story') !== false &&
                           strpos($content_to_display, 'story #123') !== false;
                },
            ],
            'Edit test definition' => [
                [],
                static function (string $content_to_display): bool {
                    return strpos($content_to_display, 'My story') !== false &&
                           strpos($content_to_display, 'story #123') !== false;
                },
            ],
            'Edit backlog definition' => [
                ['aid' => '123'],
                static function (string $content_to_display): bool {
                    return strpos($content_to_display, 'Some milestone') !== false &&
                           strpos($content_to_display, 'rel #42') !== false;
                },
            ],
        ];
    }
}
