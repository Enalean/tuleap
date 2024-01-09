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

namespace Tuleap\AgileDashboard\Artifact;

use AgileDashboard_PaneRedirectionExtractor;
use TemplateRendererFactory;
use Tracker;
use Tuleap\GlobalResponseMock;
use Tuleap\Templating\TemplateCache;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;

final class RedirectParameterInjectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    /**
     * @var mixed
     */
    private $response;
    private \PFUser $user;
    private \Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\MockObject $artifact_factory;
    private RedirectParameterInjector $injector;

    protected function setUp(): void
    {
        $this->response = $GLOBALS['Response'];

        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);
        $template_renderer_factory = new TemplateRendererFactory($template_cache);

        $this->injector = new RedirectParameterInjector(
            new AgileDashboard_PaneRedirectionExtractor(),
            $this->artifact_factory,
            $this->response,
            $template_renderer_factory->getRenderer(__DIR__ . '/../../../../templates/')
        );
    }

    public function testInjectAndInformUserAboutBacklogItemWillBeLinkedDoesNothingByDefault(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);

        self::assertEmpty($redirect->query_parameters);
    }

    public function testInjectAndInformUserAboutBacklogItemWillBeLinkedInjectsTheRequestedPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
    }

    public function testInjectAndInformUserAboutBacklogItemWillBeLinkedInjectsTheRequestedPlanningAndAsksForALinkToMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => ['42' => '101']])
            ->withParam('link-to-milestone', '1')
            ->build();

        $artifact = $this->createConfiguredMock(
            Artifact::class,
            [
                'getUri'     => '/plugins/tracker/?aid=42',
                'getTitle'   => 'Some milestone',
                'getXref'    => 'rel #42',
                'getTracker' => $this->createConfiguredMock(
                    Tracker::class,
                    ['getColor' => TrackerColor::default()]
                ),
            ]
        );

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($this->user, 101)
            ->willReturn($artifact);

        $this->response
            ->expects(self::once())
            ->method('addFeedback')
            ->with(
                \Feedback::INFO,
                self::callback(
                    static function (string $content_to_display): bool {
                        return strpos($content_to_display, 'Some milestone') !== false &&
                            strpos($content_to_display, 'rel #42') !== false;
                    }
                ),
                CODENDI_PURIFIER_FULL
            );
        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals(1, $redirect->query_parameters['link-to-milestone']);
    }

    public function testInjectAndInformUserAboutBacklogItemWillBeLinkedInjectsTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('child_milestone', '666')
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectAndInformUserAboutBacklogItemWillBeLinkedInjectsThePlanningAndTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => ['42' => '101']])
            ->withParam('child_milestone', '666')
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectParametersDoesNothingByDefault(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectParameters($request, $redirect, null);

        self::assertEmpty($redirect->query_parameters);
    }

    public function testInjectParametersInjectsTheRequestedPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectParameters($request, $redirect, null);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
    }

    public function testInjectParametersInjectsTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectParameters($request, $redirect, '666');

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectParametersInjectsThePlanningAndTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $this->injector->injectParameters($request, $redirect, '666');

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }
}
