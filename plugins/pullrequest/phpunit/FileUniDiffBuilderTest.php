<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

require_once __DIR__ . '/bootstrap.php';

class FileUniDiffBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /**
     * @var FileUniDiffBuilder
     */
    private $builder;

    /**
     * @var string
     */
    private $fixture_dir;

    /**
     * @var GitExec
     */
    private $git_exec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new FileUniDiffBuilder();

        $this->fixture_dir = $this->getTmpDir();

        $this->git_exec = new GitExec($this->fixture_dir);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('John Doe', 'john.doe@example.com');
    }

    public function testItHandlesChangedFile(): void
    {
        $file_path = "$this->fixture_dir/file";

        file_put_contents($file_path, "Contenu\n\nContenu2\nContenu3");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("add $file_path");

        file_put_contents($file_path, "Contenu3\nContenu\n\nContenu2\n\nContenu4");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("change $file_path");

        $diff = $this->builder->buildFileUnidiff($this->git_exec, $file_path, 'HEAD^', 'HEAD');

        $lines = $diff->getLines();
        $this->assertEquals(7, count($lines));

        $line = $diff->getLineFromNewOffset(1);
        $this->assertEquals(UniDiffLine::ADDED, $line->getType());
        $this->assertEquals(null, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(2);
        $this->assertEquals(UniDiffLine::KEPT, $line->getType());
        $this->assertEquals(1, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(3);
        $this->assertEquals(UniDiffLine::KEPT, $line->getType());
        $this->assertEquals(2, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(4);
        $this->assertEquals(UniDiffLine::KEPT, $line->getType());
        $this->assertEquals(3, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(5);
        $this->assertEquals(UniDiffLine::ADDED, $line->getType());
        $this->assertEquals(null, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(6);
        $this->assertEquals(UniDiffLine::ADDED, $line->getType());
        $this->assertEquals(null, $line->getOldOffset());

        $line = $diff->getLineFromOldOffset(4);
        $this->assertEquals(UniDiffLine::REMOVED, $line->getType());
        $this->assertEquals(null, $line->getNewOffset());
    }

    public function testItHandlesDeletedFile(): void
    {
        $file_path = "$this->fixture_dir/file";

        file_put_contents($file_path, "Contenu\nContenu2");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("add $file_path");

        $this->git_exec->rm($file_path);
        $this->git_exec->commit("rm $file_path");

        $diff = $this->builder->buildFileUnidiff($this->git_exec, $file_path, 'HEAD^', 'HEAD');

        $lines = $diff->getLines();
        $this->assertEquals(2, count($lines));

        $this->assertEquals(UniDiffLine::REMOVED, $lines[1]->getType());
        $this->assertEquals(1, $lines[1]->getOldOffset());
        $this->assertEquals(null, $lines[1]->getNewOffset());

        $this->assertEquals(UniDiffLine::REMOVED, $lines[2]->getType());
        $this->assertEquals(2, $lines[2]->getOldOffset());
        $this->assertEquals(null, $lines[2]->getNewOffset());
    }

    public function testItHandlesAddedFile(): void
    {
        $file_path  = "$this->fixture_dir/file";
        $file2_path = "$this->fixture_dir/file2";

        file_put_contents($file_path, "Contenu\nContenu2");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("add $file_path");

        file_put_contents($file2_path, "Contenu\nContenu2");
        $this->git_exec->add($file2_path);
        $this->git_exec->commit("add $file2_path");

        $diff = $this->builder->buildFileUnidiff($this->git_exec, $file2_path, 'HEAD^', 'HEAD');

        $lines = $diff->getLines();
        $this->assertEquals(2, count($lines));

        $this->assertEquals(UniDiffLine::ADDED, $lines[1]->getType());
        $this->assertEquals(1, $lines[1]->getNewOffset());
        $this->assertEquals(null, $lines[1]->getOldOffset());

        $this->assertEquals(UniDiffLine::ADDED, $lines[2]->getType());
        $this->assertEquals(2, $lines[2]->getNewOffset());
        $this->assertEquals(null, $lines[2]->getOldOffset());
    }
}
