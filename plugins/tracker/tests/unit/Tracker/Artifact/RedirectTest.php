<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Redirect;

final class RedirectTest extends TestCase
{
    /**
     * @testWith ["/base/url"]
     *           ["/base/url/"]
     *           ["/base/url//"]
     */
    public function testRedirectURLCanBeBuilt(string $base_url): void
    {
        $redirect                   = new Tracker_Artifact_Redirect();
        $redirect->base_url         = $base_url;
        $redirect->query_parameters = ['paramA' => '1', 'paramB' => '2'];

        $this->assertEquals('/base/url/?paramA=1&paramB=2', $redirect->toUrl());
    }

    public function testDetectWhenRedirectionStaysInTracker(): void
    {
        $redirection = new Tracker_Artifact_Redirect();
        $redirection->mode = Tracker_Artifact_Redirect::STATE_STAY_OR_CONTINUE;
        $this->assertTrue($redirection->stayInTracker());
        $redirection->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $this->assertFalse($redirection->stayInTracker());
    }
}
