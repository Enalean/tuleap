<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'bootstrap.php';

class GitMarkdownFileTestPHP53 extends TuleapTestCase {

    private $git_exec;
    private $git_markdown_file;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();
        $this->git_exec = mock('Git_Exec');
        $this->git_markdown_file = new GitMarkdownFile($this->git_exec);
    }

    public function testGetMarkdownFilesContent() {
        $files_names = array("test.java", "test.markdown", "readme.md", "test.c", "test.mkd");
        stub($this->git_exec)->lsTree('commit', 'node')->returns($files_names);

        $test_md_content = "Content of test.md\n==========";
        stub($this->git_exec)->getFileContent('commit', 'readme.md')->returns($test_md_content);

        $expected_result = array(
            'file_name'    => "readme.md",
            'file_content' => Michelf\MarkdownExtra::defaultTransform($test_md_content)
        );

        $this->assertEqual($this->git_markdown_file->getReadmeFileContent('path', 'node', 'commit'), $expected_result);
    }
}
