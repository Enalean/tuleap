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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\GitPHP\Blob;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Project;

final class LinkToGitFileBlobFinderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Commit
     */
    private $current_commit;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->current_commit = \Mockery::mock(Commit::class);
        $this->project        = \Mockery::mock(Project::class);

        $this->current_commit->shouldReceive('GetProject')->andReturn($this->project);
    }

    /**
     * @dataProvider dataProviderToExistingFile
     */
    public function testCanFindsExistingFile(string $readme_path, string $url, string $expected_path): void
    {
        $blob_finder = new LinkToGitFileBlobFinder($readme_path, $this->current_commit);

        $this->current_commit->shouldReceive('PathToHash')->andReturn('blob_ref');
        $this->current_commit->shouldReceive('GetHash')->andReturn('commit_ref');
        $linked_blob = \Mockery::mock(Blob::class);
        $linked_blob->shouldReceive('GetHash')->andReturn('blob_ref');
        $this->project->shouldReceive('GetBlob')->andReturn($linked_blob);

        $found_blob = $blob_finder->findBlob($url);
        $this->assertEquals($expected_path, $found_blob->getPath());
        $this->assertEquals('blob_ref', $found_blob->getBlobRef());
        $this->assertEquals('commit_ref', $found_blob->getCommitRef());
    }

    public function dataProviderToExistingFile(): array
    {
        return [
            'Top folder implicit relative path'                => ['README.md', 'image.jpg','image.jpg'],
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
        $this->current_commit->shouldReceive('PathToHash')->andReturn('');
        $this->project->shouldReceive('GetBlob')->andReturn(null);
        $this->assertNull($blob_finder->findBlob('https://example.com'));
    }
}
