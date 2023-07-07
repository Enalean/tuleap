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

use Tuleap\TemporaryTestDirectory;

final class FileUniDiffBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

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

        file_put_contents($file_path, "# Title\n\n0.000\nBar");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("add $file_path");

        file_put_contents($file_path, "# Title\n\n0\nBar\nBaz");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("change $file_path");

        $diff = $this->builder->buildFileUnidiff($this->git_exec, $file_path, 'HEAD^', 'HEAD');

        $lines = $diff->getLines();
        self::assertEquals(7, count($lines));

        $line = $diff->getLineFromNewOffset(1);
        self::assertEquals(UniDiffLine::KEPT, $line->getType());
        self::assertEquals(1, $line->getOldOffset());
        self::assertEquals('# Title', $line->getContent());

        $line = $diff->getLineFromNewOffset(2);
        self::assertEquals(UniDiffLine::KEPT, $line->getType());
        self::assertEquals(2, $line->getOldOffset());
        self::assertEquals('', $line->getContent());

        $line = $diff->getLineFromNewOffset(3);
        self::assertEquals(UniDiffLine::ADDED, $line->getType());
        self::assertEquals(null, $line->getOldOffset());
        self::assertEquals('0', $line->getContent());

        $line = $diff->getLineFromNewOffset(4);
        self::assertEquals(UniDiffLine::ADDED, $line->getType());
        self::assertEquals(null, $line->getOldOffset());
        self::assertEquals('Bar', $line->getContent());

        $line = $diff->getLineFromNewOffset(5);
        self::assertEquals(UniDiffLine::ADDED, $line->getType());
        self::assertEquals(null, $line->getOldOffset());
        self::assertEquals('Baz', $line->getContent());

        $line = $diff->getLineFromOldOffset(3);
        self::assertEquals(UniDiffLine::REMOVED, $line->getType());
        self::assertEquals(null, $line->getNewOffset());
        self::assertEquals('0.000', $line->getContent());

        $line = $diff->getLineFromOldOffset(4);
        self::assertEquals(UniDiffLine::REMOVED, $line->getType());
        self::assertEquals(null, $line->getNewOffset());
        self::assertEquals('Bar', $line->getContent());
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
        self::assertEquals(2, count($lines));

        self::assertEquals(UniDiffLine::REMOVED, $lines[1]->getType());
        self::assertEquals(1, $lines[1]->getOldOffset());
        self::assertEquals(null, $lines[1]->getNewOffset());

        self::assertEquals(UniDiffLine::REMOVED, $lines[2]->getType());
        self::assertEquals(2, $lines[2]->getOldOffset());
        self::assertEquals(null, $lines[2]->getNewOffset());
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
        self::assertEquals(2, count($lines));

        self::assertEquals(UniDiffLine::ADDED, $lines[1]->getType());
        self::assertEquals(1, $lines[1]->getNewOffset());
        self::assertEquals(null, $lines[1]->getOldOffset());

        self::assertEquals(UniDiffLine::ADDED, $lines[2]->getType());
        self::assertEquals(2, $lines[2]->getNewOffset());
        self::assertEquals(null, $lines[2]->getOldOffset());
    }
}
