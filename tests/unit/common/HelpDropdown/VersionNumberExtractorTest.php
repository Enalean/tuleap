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

namespace Tuleap\HelpDropdown;

use PHPUnit\Framework\TestCase;

class VersionNumberExtractorTest extends TestCase
{
    /**
     * @dataProvider dataProviderTuleapVersions
     */
    public function testItExtractsTheTuleapVersionForTheReleaseNote(
        string $tuleap_version,
        string $expected_tuleap_release_note_version
    ): void {
        $extractor = new VersionNumberExtractor();

        $this->assertSame(
            $expected_tuleap_release_note_version,
            $extractor->extractReleaseNoteTuleapVersion($tuleap_version)
        );
    }

    public function dataProviderTuleapVersions(): array
    {
        return [
            ['12.0', '12-0'],
            ['12.0.99.127', '12-0'],
            ['12.0-4', '12-0'],
            ['11.18', '11-18'],
            ['11.18.99.58', '11-18'],
            ['11.18-9', '11-18'],
        ];
    }

    public function testItThrowsAnExceptionIfProvidedTuleapVersionIsNotWellFormed(): void
    {
        $extractor = new VersionNumberExtractor();

        $this->expectException(TuleapVersionNotExtractedException::class);

        $extractor->extractReleaseNoteTuleapVersion('aaaaaa');
    }
}
