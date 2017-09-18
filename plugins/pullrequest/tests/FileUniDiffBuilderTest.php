<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use TuleapTestCase;

require_once 'bootstrap.php';

class FileUniDiffBuilderTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->builder = new FileUniDiffBuilder();

        $this->fixture_dir = '/tmp/tuleap-pullrequest-fileunidiffbuilder-test_'.rand(0, 99999999);
        mkdir($this->fixture_dir);
        system("cd $this->fixture_dir && git init 2>&1 >/dev/null");

        $this->git_exec = new GitExec($this->fixture_dir);
        $this->git_exec->setLocalCommiter('John Doe', 'john.doe@example.com');
    }

    public function tearDown()
    {
        system("rm -rf $this->fixture_dir");

        parent::tearDown();
    }

    public function itHandlesChangedFile()
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
        $this->assertEqual(7, count($lines));

        $line = $diff->getLineFromNewOffset(1);
        $this->assertEqual(UniDiffLine::ADDED, $line->getType());
        $this->assertEqual(NULL, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(2);
        $this->assertEqual(UniDiffLine::KEPT, $line->getType());
        $this->assertEqual(1, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(3);
        $this->assertEqual(UniDiffLine::KEPT, $line->getType());
        $this->assertEqual(2, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(4);
        $this->assertEqual(UniDiffLine::KEPT, $line->getType());
        $this->assertEqual(3, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(5);
        $this->assertEqual(UniDiffLine::ADDED, $line->getType());
        $this->assertEqual(NULL, $line->getOldOffset());

        $line = $diff->getLineFromNewOffset(6);
        $this->assertEqual(UniDiffLine::ADDED, $line->getType());
        $this->assertEqual(NULL, $line->getOldOffset());

        $line = $diff->getLineFromOldOffset(4);
        $this->assertEqual(UniDiffLine::REMOVED, $line->getType());
        $this->assertEqual(NULL, $line->getNewOffset());
    }

    public function itHandlesDeletedFile()
    {
        $file_path = "$this->fixture_dir/file";

        file_put_contents($file_path, "Contenu\nContenu2");
        $this->git_exec->add($file_path);
        $this->git_exec->commit("add $file_path");

        $this->git_exec->rm($file_path);
        $this->git_exec->commit("rm $file_path");

        $diff = $this->builder->buildFileUnidiff($this->git_exec, $file_path, 'HEAD^', 'HEAD');

        $lines = $diff->getLines();
        $this->assertEqual(2, count($lines));

        $this->assertEqual(UniDiffLine::REMOVED, $lines[1]->getType());
        $this->assertEqual(1, $lines[1]->getOldOffset());
        $this->assertEqual(NULL, $lines[1]->getNewOffset());

        $this->assertEqual(UniDiffLine::REMOVED, $lines[2]->getType());
        $this->assertEqual(2, $lines[2]->getOldOffset());
        $this->assertEqual(NULL, $lines[2]->getNewOffset());

    }

    public function itHandlesAddedFile()
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
        $this->assertEqual(2, count($lines));

        $this->assertEqual(UniDiffLine::ADDED, $lines[1]->getType());
        $this->assertEqual(1, $lines[1]->getNewOffset());
        $this->assertEqual(NULL, $lines[1]->getOldOffset());

        $this->assertEqual(UniDiffLine::ADDED, $lines[2]->getType());
        $this->assertEqual(2, $lines[2]->getNewOffset());
        $this->assertEqual(NULL, $lines[2]->getOldOffset());
    }
}
