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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\HTTPRequestBuilder;

class RedirectParameterInjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInjectParametersWithChildMilestoneFromRequestDoesNothingByDefault(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithChildMilestoneFromRequest($request, $redirect);

        self::assertEmpty($redirect->query_parameters);
    }

    public function testInjectParametersWithChildMilestoneFromRequestInjectsTheRequestedPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithChildMilestoneFromRequest($request, $redirect);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
    }

    public function testInjectParametersWithChildMilestoneFromRequestInjectsTheRequestedPlanningAndAsksForALinkToMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => ['42' => '101']])
            ->withParam('link-to-milestone', '1')
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithChildMilestoneFromRequest($request, $redirect);

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals(1, $redirect->query_parameters['link-to-milestone']);
    }

    public function testInjectParametersWithChildMilestoneFromRequestInjectsTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('child_milestone', '666')
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithChildMilestoneFromRequest($request, $redirect);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectParametersWithChildMilestoneFromRequestInjectsThePlanningAndTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => ['42' => '101']])
            ->withParam('child_milestone', '666')
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithChildMilestoneFromRequest($request, $redirect);

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectParametersWithGivenChildMilestoneDoesNothingByDefault(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithGivenChildMilestone($request, $redirect, null);

        self::assertEmpty($redirect->query_parameters);
    }

    public function testInjectParametersWithGivenChildMilestoneInjectsTheRequestedPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithGivenChildMilestone($request, $redirect, null);

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
    }

    public function testInjectParametersWithGivenChildMilestoneInjectsTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithGivenChildMilestone($request, $redirect, '666');

        self::assertCount(1, $redirect->query_parameters);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }

    public function testInjectParametersWithGivenChildMilestoneInjectsThePlanningAndTheChildMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => ['42' => '101']])
            ->build();

        $redirect = new \Tracker_Artifact_Redirect();

        $injector = new RedirectParameterInjector(new AgileDashboard_PaneRedirectionExtractor());
        $injector->injectParametersWithGivenChildMilestone($request, $redirect, '666');

        self::assertCount(2, $redirect->query_parameters);
        self::assertEquals('101', $redirect->query_parameters['planning[details][42]']);
        self::assertEquals('666', $redirect->query_parameters['child_milestone']);
    }
}
