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

namespace Tuleap\Git\Repository\View;

use PHPUnit\Framework\TestCase;

final class LanguageDetectorForPrismJSTest extends TestCase
{
    public function testDetectLanguageFromTheFileName(): void
    {
        $detector = new LanguageDetectorForPrismJS();
        $this->assertEquals('cmake', $detector->getLanguage('CMakeLists.txt'));
        $this->assertEquals('dockerfile', $detector->getLanguage('Dockerfile'));
    }

    public function testDetectLanguageFromAKnownExtension(): void
    {
        $detector = new LanguageDetectorForPrismJS();
        $this->assertEquals('markdown', $detector->getLanguage('README.mkd'));
    }

    public function testDetectLanguageFromAKnownCompositeExtension(): void
    {
        $detector = new LanguageDetectorForPrismJS();
        $this->assertEquals('cmake', $detector->getLanguage('a.cmake.in'));
    }

    /**
     * @testWith ["a.php"]
     *           ["a.foo.php"]
     */
    public function testFallBackToTheExtensionIfNothingMatchesSomethingKnow(string $filename): void
    {
        $detector = new LanguageDetectorForPrismJS();
        $this->assertEquals('php', $detector->getLanguage($filename));
    }
}
