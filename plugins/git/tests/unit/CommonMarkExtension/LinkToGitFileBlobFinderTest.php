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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\GitPHP\Blob;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Project;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkToGitFileBlobFinderTest extends TestCase
{
    private readonly MockObject&Commit $current_commit;
    private readonly MockObject&Project $project;

    protected function setUp(): void
    {
        $this->current_commit = $this->createMock(Commit::class);
        $this->project        = $this->createMock(Project::class);

        $this->current_commit->method('GetProject')->willReturn($this->project);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderToExistingFile')]
    public function testCanFindsExistingFile(string $readme_path, string $url, string $expected_path): void
    {
        $blob_finder = new LinkToGitFileBlobFinder($readme_path, $this->current_commit);

        $this->current_commit->method('PathToHash')->willReturn('blob_ref');
        $this->current_commit->method('GetHash')->willReturn('commit_ref');
        $linked_blob = $this->createMock(Blob::class);
        $linked_blob->method('GetHash')->willReturn('blob_ref');
        $this->project->method('GetBlob')->willReturn($linked_blob);

        $found_blob = $blob_finder->findBlob($url);
        self::assertSame($expected_path, $found_blob->getPath());
        self::assertSame('blob_ref', $found_blob->getBlobRef());
        self::assertSame('commit_ref', $found_blob->getCommitRef());
    }

    public static function dataProviderToExistingFile(): array
    {
        return [
            'Top folder implicit relative path'                => ['README.md', 'image.jpg','image.jpg'],
            'Top folder implicit relative path with spaces'    => ['README.md', 'some%20space.jpg','some space.jpg'],
            'Top folder explicit relative path'                => ['README.md', './image.jpg','image.jpg'],
            'Top folder relative path outside top folder'      => ['README.md', '../image.jpg','image.jpg'],
            'Top folder with link to sub folder file'          => ['README.md', 'sub/image.jpg','sub/image.jpg'],
            'Sub folder with link to sub folder file'          => ['sub/README.md', 'image.jpg','sub/image.jpg'],
            'Sub folder with link to top folder file'          => ['sub/README.md', '../image.jpg','image.jpg'],
            'Sub folder with absolute path to top folder file' => ['sub/README.md', '/image.jpg','image.jpg'],
            'Sub folder with absolute path to sub folder file' => ['sub/README.md', '/sub/image.jpg','sub/image.jpg'],
        ];
    }

    public function testReturnsNullIfFileCannotBeFound(): void
    {
        $blob_finder = new LinkToGitFileBlobFinder('README.md', $this->current_commit);
        $this->current_commit->method('PathToHash')->willReturn('');
        $this->project->method('GetBlob')->willReturn(null);
        self::assertNull($blob_finder->findBlob('https://example.com'));
    }
}
