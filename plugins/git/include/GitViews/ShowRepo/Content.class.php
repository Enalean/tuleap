<?php
/**
 * Copyright (c) Enalean, 2013-2017. All Rights Reserved.
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

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Git\History\GitPhpAccessLogger;

class GitViews_ShowRepo_Content {

    const PAGE_TYPE       = 'a';
    const PAGE_TYPE_TREE  = 'tree';
    const FOLDER_TREE     = 'f';
    const OLD_COMMIT_TREE = 'hb';

    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var GitRepository
     */
    protected $repository;
    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;
    /**
     * @var GitPhpAccessLogger
     */
    private $access_logger;

    public function __construct(
        GitRepository $repository,
        GitViews_GitPhpViewer $gitphp_viewer,
        HTTPRequest $request,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPhpAccessLogger $access_logger
    ) {
        $this->repository         = $repository;
        $this->gitphp_viewer      = $gitphp_viewer;
        $this->request            = $request;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->access_logger      = $access_logger;
    }

    public function display()
    {
        $html = '';

        if ($this->repository->isCreated()) {
            $is_download = false;
            $html       .= $this->gitphp_viewer->getContent($is_download);

            $this->access_logger->logAccess($this->repository, $this->request->getCurrentUser());
        } else {
            $html .= $this->getWaitingForRepositoryCreationInfo();
        }
        if ($this->isATreePage()) {
            $html .= $this->getMarkdownFilesDiv();
        }

        echo $html;
    }

    private function isATreePage()
    {
        return ! $this->request->exist(self::PAGE_TYPE) ||
            $this->request->get(self::PAGE_TYPE) === self::PAGE_TYPE_TREE;
    }

    private function getMarkdownFilesDiv()
    {
        $commit_sha1       = $this->getCurrentCommitSha1();
        $node              = $this->getCurrentNode();
        $repository_path   = ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/' . $this->repository->getPath();
        $git_markdown_file = new GitMarkdownFile(
            new Git_Exec($repository_path, $repository_path),
            new ContentInterpretor()
        );

        $readme_file = $git_markdown_file->getReadmeFileContent($node, $commit_sha1);

        if ($readme_file) {
            $presenter = new ReadmeMarkdownPresenter($readme_file['file_name'], $readme_file['file_content']);
            $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

            return $renderer->renderToString('readme_markdown', $presenter);
        }
    }

    private function getCurrentNode()
    {
        if ($this->request->exist(self::FOLDER_TREE)) {
            return $this->request->get(self::FOLDER_TREE).'/';
        }

        return '';
    }

    private function getCurrentCommitSha1()
    {
        if ($this->request->exist(self::OLD_COMMIT_TREE)) {
            return $this->request->get(self::OLD_COMMIT_TREE);
        }

        return 'HEAD';
    }

    private function getWaitingForRepositoryCreationInfo()
    {
        $html = '<div class="tlp-alert-info git-waiting-for-repo-creation">';

        $html .= $GLOBALS['Language']->getText('plugin_git', 'waiting_for_repo_creation');

        $default_mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($this->repository);

        if ($default_mirrors) {
            $default_mirrors_names = array_map(
                array($this, 'extractMirrorName'),
                $default_mirrors
            );

            $html .= '<br/>';
            $html .= $GLOBALS['Language']->getText(
                'plugin_git',
                'waiting_for_repo_creation_default_mirrors',
                implode(', ', $default_mirrors_names)
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function extractMirrorName(Git_Mirror_Mirror $mirror)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return $purifier->purify($mirror->name);
    }

}
