<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\Markdown\ContentInterpretor;

class GitMarkdownFile {

    /**
     * @var ContentInterpretor
     */
    private $markdown_content_interpretor;

    /** @var Git_Exec */
    private $git_exec;

    public function __construct(Git_Exec $git_exec, ContentInterpretor $markdown_content_interpretor) {
        $this->git_exec                     = $git_exec;
        $this->markdown_content_interpretor = $markdown_content_interpretor;
    }

    /**
     * Get the first markdown file name/content for a folder in a Git repository.
     *
     * @param string $node Folder path from repository
     * @param string $commit_sha1 Sha-1 of the comit you want to inspect
     *
     * @return array
     */
    public function getReadmeFileContent($node, $commit_sha1) {
        $readme_file = $this->getReadmeFile($node, $commit_sha1);
        if ($readme_file){
            return $this->getFormatedMarkdown($readme_file, $commit_sha1);
        }

        return false;
    }

    private function getFormatedMarkdown($file_name, $commit_sha1) {
        $content         = $this->git_exec->getFileContent($commit_sha1, $file_name);
        $content_in_form = $this->markdown_content_interpretor->getInterpretedContent($content);

        return array(
            'file_name'    => $file_name,
            'file_content' => $content_in_form
        );
    }

    private function getReadmeFile($node, $commit_sha1) {
        try {
            $files_list          = $this->git_exec->lsTree($commit_sha1, $node);
        } catch (Git_Command_Exception $ex) {
            return false;
        }
        $path                = preg_quote($node, DIRECTORY_SEPARATOR);
        $markdown_files_list = array_values(
            preg_grep('/^'. $path .'readme\.(markdown|mdown|mkdn|md|mkd|mdwn|mdtxt|mdtext|text)$/i', $files_list)
        );

        if (isset($markdown_files_list[0])) {
            return $markdown_files_list[0];
        }

        return false;
    }
}
