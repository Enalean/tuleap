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

namespace Tuleap\Git\CommonMarkExtension;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class LinkToGitFileExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LinkToGitFileBlobFinder
     */
    private $blob_finder;
    /**
     * @var CommonMarkConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $environment       = Environment::createCommonMarkEnvironment();
        $this->blob_finder = \Mockery::mock(LinkToGitFileBlobFinder::class);
        $environment->addExtension(new LinkToGitFileExtension($this->blob_finder));
        $this->converter = new CommonMarkConverter([], $environment);
    }

    public function testDirectLinkIsConvertedIfItPointsToAGitFile(): void
    {
        $this->blob_finder->shouldReceive('findBlob')->andReturn(
            new BlobPointedByURL('blob_ref', 'commit_ref', 'file_in_repo.txt')
        );
        $result = $this->converter->convertToHtml('[](file_in_repo.txt)');

        $this->assertEquals("<p><a href=\"?a=blob&amp;hb=commit_ref&amp;h=blob_ref&amp;f=file_in_repo.txt\"></a></p>\n", $result);
    }

    public function testImageLinkIsConvertedIfItPointsToAGitFile(): void
    {
        $this->blob_finder->shouldReceive('findBlob')->andReturn(
            new BlobPointedByURL('blob_ref', 'commit_ref', 'image_in_repo.jpg')
        );
        $result = $this->converter->convertToHtml('![](image_in_repo.jpg)');

        $this->assertEquals("<p><img src=\"?a=blob_plain&amp;hb=commit_ref&amp;h=blob_ref&amp;f=image_in_repo.jpg\" alt=\"\" /></p>\n", $result);
    }

    public function testDirectLinkIsLeftIntactIfItDoesNotPointToAGitFile(): void
    {
        $this->blob_finder->shouldReceive('findBlob')->andReturn(null);

        $result = $this->converter->convertToHtml('[](https://example.com)');

        $this->assertEquals("<p><a href=\"https://example.com\"></a></p>\n", $result);
    }

    public function testImageLinkIsLeftIntactIfItDoesNotPointToAGitFile(): void
    {
        $this->blob_finder->shouldReceive('findBlob')->andReturn(null);

        $result = $this->converter->convertToHtml('![](https://example.com/a.jpg)');

        $this->assertEquals("<p><img src=\"https://example.com/a.jpg\" alt=\"\" /></p>\n", $result);
    }
}
