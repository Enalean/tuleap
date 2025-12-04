<?php
/**
 * Copyright (c) Enalean 2021 - Present. All Rights Reserved.
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

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HomeServiceRedirectionExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsTrueIfRequestMustRedirectToADHomepage(): void
    {
        $extractor = new HomeServiceRedirectionExtractor();
        $request   = new \Tuleap\HTTPRequest([
            'agiledashboard' => [
                'home' => '1',
            ],
        ]);

        assertTrue(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );
    }

    public function testItReturnsFalseIfRequestMustNotRedirectToADHomepage(): void
    {
        $extractor = new HomeServiceRedirectionExtractor();

        $request = new \Tuleap\HTTPRequest([]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );

        $request = new \Tuleap\HTTPRequest([
            'agiledashboard',
        ]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );

        $request = new \Tuleap\HTTPRequest([
            'agiledashboard' => [],
        ]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );

        $request = new \Tuleap\HTTPRequest([
            'agiledashboard' => [
                'home' => 'whatever',
            ],
        ]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );

        $request = new \Tuleap\HTTPRequest([
            'agiledashboard' => [
                'whatever' => '1',
            ],
        ]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );

        $request = new \Tuleap\HTTPRequest([
            'whatever' => [
                'home' => '1',
            ],
        ]);
        assertFalse(
            $extractor->mustRedirectToAgiledashboardHomepage($request)
        );
    }
}
