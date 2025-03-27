<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\FilenamePattern;

use Docman_SettingsBo;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilenameBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Docman_SettingsBo
     */
    private $docman_settings_bo;

    protected function setUp(): void
    {
        $this->docman_settings_bo = $this->createMock(Docman_SettingsBo::class);
    }

    public function testItReturnsTheOriginalFilenameWhenThePatternIsNull(): void
    {
        $this->docman_settings_bo->expects(self::never())->method('getMetadataUsage');

        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithNoPattern(),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS.jpg';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            101,
            'From The Window To the Hood',
            100,
            '',
            15
        );
        self::assertSame($original_filename, $update_filename);
    }

    public function testItReturnsTheOriginalFilenameWhenThePatternIsAnEmptyString(): void
    {
        $this->docman_settings_bo->expects(self::never())->method('getMetadataUsage');

        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithPattern(''),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS.jpg';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            101,
            'From The Window To the Hood',
            100,
            '',
            15
        );
        self::assertSame($original_filename, $update_filename);
    }

    public function testItReturnsTheNewFilenameWithTheTitleVariable(): void
    {
        $this->docman_settings_bo->expects(self::never())->method('getMetadataUsage');

        $pattern          = 'Brand-${TITLE}';
        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithPattern($pattern),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS.jpg';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            111,
            'Mercedes',
            100,
            '',
            15
        );
        self::assertSame('Brand-Mercedes.jpg', $update_filename);
    }

    public function testItReturnsTheNewFilenameWithTheItemIdVariable(): void
    {
        $this->docman_settings_bo->expects(self::never())->method('getMetadataUsage');

        $pattern          = 'Brand-Mercedes-rejected-Not a Mercredes wow-#${ID}';
        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithPattern($pattern),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS.jpg';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            111,
            'Mercedes',
            103,
            'Not a Mercredes wow',
            15
        );
        self::assertSame('Brand-Mercedes-rejected-Not a Mercredes wow-#15.jpg', $update_filename);
    }

    public function testItReturnsTheNewFilenameUsingSomeVariable(): void
    {
        $this->docman_settings_bo->expects($this->once())->method('getMetadataUsage')->willReturn('1');

        $pattern          = 'Brand-${TITLE}-${STATUS}-Not a Mercredes wow-#${ID}';
        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithPattern($pattern),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS.jpg';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            111,
            'BMW',
            103,
            'Not a Mercredes wow',
            15
        );
        self::assertSame('Brand-BMW-rejected-Not a Mercredes wow-#15.jpg', $update_filename);
    }

    public function testItReturnsTheNewFilenameWithoutExtension(): void
    {
        $this->docman_settings_bo->expects($this->once())->method('getMetadataUsage')->willReturn('1');

        $pattern          = 'Brand-${TITLE}-${STATUS}-Not a Mercredes wow-#${ID}';
        $filename_builder = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithPattern($pattern),
            new ItemStatusMapper($this->docman_settings_bo)
        );

        $original_filename = 'M2 CS';

        $update_filename = $filename_builder->buildFilename(
            $original_filename,
            111,
            'BMW',
            103,
            'Not a Mercredes wow',
            15
        );
        self::assertSame('Brand-BMW-rejected-Not a Mercredes wow-#15', $update_filename);
    }
}
