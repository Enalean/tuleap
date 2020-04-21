<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\GitPHP;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TreeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * 100644 blob   81f8847ccc5c49931663dedfed16c2c3dc9ea69d    .gitmodules
     * 100644 blob   59c50daea6b2c23544c77629a2af4d1134ee0bc3    f1
     * 040000 tree   0543c7a05785554d8f80b7a4b40bc64add26b7d2    folder1
     * 160000 commit 6e8099ef4091a5634bfc3e632eaf3c5ddd6c2787    vault
     */
    public const TREE_CONTENT_BASE64 = 'MTAwNjQ0IC5naXRtb2R1bGVzAIH4hHzMXEmTFmPe3+0WwsPcnqadMTAwNjQ0IGYxAFnFDa6mssI1RMd2KaKvTRE07gvDNDAwMDAgZm9sZGVyMQAFQ8egV4VVTY+At6S0C8ZK3Sa30jE2MDAwMCB2YXVsdABugJnvQJGlY0v8PmMurzxd3Wwnhw==';

    public function testContentIsRetrieved()
    {
        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('GetObject')->with('f3bee1d2acaeed2c516f262a57928cde54fc4423')
            ->andReturns(base64_decode(self::TREE_CONTENT_BASE64));
        $project->shouldReceive('GetBlob')->with('81f8847ccc5c49931663dedfed16c2c3dc9ea69d')
            ->andReturns(\Mockery::spy(Blob::class))->once();
        $project->shouldReceive('GetBlob')->with('59c50daea6b2c23544c77629a2af4d1134ee0bc3')
            ->andReturns(\Mockery::spy(Blob::class))->once();
        $project->shouldReceive('GetTree')->with('0543c7a05785554d8f80b7a4b40bc64add26b7d2')
            ->andReturns(\Mockery::spy(Tree::class))->once();

        $tree = new Tree($project, 'f3bee1d2acaeed2c516f262a57928cde54fc4423');

        $content = $tree->GetContents();
        $this->assertSame(count($content), 4);

        $has_expected_submodule = false;
        foreach ($content as $object) {
            if ($object->isSubmodule() && $object->GetHash() === '6e8099ef4091a5634bfc3e632eaf3c5ddd6c2787') {
                $has_expected_submodule = true;
            }
        }
        $this->assertTrue($has_expected_submodule);
    }
}
