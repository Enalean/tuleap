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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkToGitFileExtensionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private readonly MockObject&LinkToGitFileBlobFinder $blob_finder;
    private readonly MarkdownConverter $converter;

    protected function setUp(): void
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->blob_finder = $this->createMock(LinkToGitFileBlobFinder::class);
        $environment->addExtension(new LinkToGitFileExtension($this->blob_finder));
        $this->converter = new MarkdownConverter($environment);
    }

    public function testDirectLinkIsConvertedIfItPointsToAGitFile(): void
    {
        $this->blob_finder->method('findBlob')->willReturn(
            new BlobPointedByURL('blob_ref', 'commit_ref', 'file_in_repo.txt')
        );
        $result = $this->converter->convert('[](file_in_repo.txt)');

        self::assertSame("<p><a href=\"?a=blob&amp;hb=commit_ref&amp;h=blob_ref&amp;f=file_in_repo.txt\"></a></p>\n", $result->getContent());
    }

    public function testImageLinkIsConvertedIfItPointsToAGitFile(): void
    {
        $this->blob_finder->method('findBlob')->willReturn(
            new BlobPointedByURL('blob_ref', 'commit_ref', 'image_in_repo.jpg')
        );
        $result = $this->converter->convert('![](image_in_repo.jpg)');

        self::assertSame("<p><img src=\"?a=blob_plain&amp;hb=commit_ref&amp;h=blob_ref&amp;f=image_in_repo.jpg\" alt=\"\" /></p>\n", $result->getContent());
    }

    public function testDirectLinkIsLeftIntactIfItDoesNotPointToAGitFile(): void
    {
        $this->blob_finder->method('findBlob')->willReturn(null);

        $result = $this->converter->convert('[](https://example.com)');

        self::assertSame("<p><a href=\"https://example.com\"></a></p>\n", $result->getContent());
    }

    public function testImageLinkIsLeftIntactIfItDoesNotPointToAGitFile(): void
    {
        $this->blob_finder->method('findBlob')->willReturn(null);

        $result = $this->converter->convert('![](https://example.com/a.jpg)');

        self::assertSame("<p><img src=\"https://example.com/a.jpg\" alt=\"\" /></p>\n", $result->getContent());
    }
}
