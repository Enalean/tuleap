<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Chart {

    use PHPUnit\Framework\TestCase;
    use Tuleap\Test\Network\HTTPHeaderStack;

    function header($header, $replace = true, $http_response_code = null): void
    {
        \Tuleap\header($header, $replace, $http_response_code);
    }

    final class ChartTest extends TestCase
    {
        // See https://tools.ietf.org/html/rfc2083#section-12.11
        private const PNG_FILE_SIGNATURE = "\x89PNG\r\n\x1a\n";

        protected function tearDown(): void
        {
            HTTPHeaderStack::clear();
            unset($GLOBALS['__jpg_err_locale'], $GLOBALS['__jpg_OldHandler']);
        }

        public function testDisplayingAnEmptyStringMessageWorks(): void
        {
            $chart = new Chart();
            $chart->displayMessage('');

            $http_headers = HTTPHeaderStack::getStack();
            $this->assertCount(1, $http_headers);
            $this->assertEquals('Content-type: image/png', $http_headers[0]->getHeader());
            $this->expectOutputRegex('/^' . self::PNG_FILE_SIGNATURE . '/');
        }
    }
}
