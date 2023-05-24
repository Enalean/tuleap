<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
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

use GitPHP\Commit\TreePresenter;
use Tuleap\Git\CommonMarkExtension\LinkToGitFileBlobFinder;
use Tuleap\Git\CommonMarkExtension\LinkToGitFileExtension;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;

class Controller_Tree extends ControllerBase // @codingStandardsIgnoreLine
{
    public const README_FILE_PATTERN = '/^readme\.(markdown|mdown|mkdn|md|mkd|mdwn|mdtxt|mdtext|text)$/i';

    public function __construct()
    {
        parent::__construct();
        if (! $this->project) {
            throw new MessageException(dgettext("gitphp", 'Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate() // @codingStandardsIgnoreLine
    {
        return 'tuleap/tree.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param bool $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        if ($local) {
            return dgettext("gitphp", 'tree');
        }
        return 'tree';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        }

        if (! (isset($this->params['hashbase']) || isset($this->params['hash']))) {
            $this->params['hashbase'] = 'HEAD';
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        if (! isset($this->params['hashbase'])) {
            // TODO: write a lookup for hash (tree) -> hashbase (commithash) and remove this
            throw new \Exception('Hashbase is required');
        }

        $commit = $this->project->GetCommit($this->params['hashbase']);

        $this->tpl->assign('commit', $commit);

        if ($commit === null) {
            return;
        }

        if (! isset($this->params['hash'])) {
            if (isset($this->params['file'])) {
                $this->params['hash'] = $commit->PathToHash($this->params['file']);
            } else {
                $this->params['hash'] = $commit->GetTree()->GetHash();
            }
        }

        $tree = $this->project->GetTree($this->params['hash']);
        if (! $tree) {
            throw new NotFoundException();
        }

        $readme_tree_item = $this->getReadmeTreeItem($tree);
        $this->tpl->assign('readme_content', $readme_tree_item);
        if ($readme_tree_item !== null) {
            $code_block_features = new \Tuleap\Markdown\CodeBlockFeatures();
            $content_interpretor = CommonMarkInterpreter::build(
                \Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension($code_block_features),
                new LinkToGitFileExtension(new LinkToGitFileBlobFinder($readme_tree_item->GetFullPath(), $commit))
            );
            $this->tpl->assign(
                'readme_content_interpreted',
                $content_interpretor->getInterpretedContent(
                    $readme_tree_item->GetData()
                )
            );
            $core_assets = new \Tuleap\Layout\IncludeCoreAssets();
            $GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($core_assets, 'syntax-highlight.js'));
            if ($code_block_features->isMermaidNeeded()) {
                $js_asset = new \Tuleap\Layout\JavascriptViteAsset(
                    new \Tuleap\Layout\IncludeViteAssets(
                        __DIR__ . '/../../../../../src/scripts/mermaid-diagram-element/frontend-assets',
                        '/assets/core/mermaid-diagram-element',
                    ),
                    'src/index.ts',
                );
                $GLOBALS['HTML']->addJavascriptAsset($js_asset);
            }
        }

        if (! $tree->GetCommit()) {
            $tree->SetCommit($commit);
        }
        if (isset($this->params['file'])) {
            $tree->SetPath($this->params['file']);
        }

        $this->tpl->assign('tree_presenter', new TreePresenter($tree));
        $this->tpl->assign('tree', $tree);
    }

    /**
     * @return null|Blob
     */
    private function getReadmeTreeItem(Tree $tree)
    {
        foreach ($tree->GetContents() as $content) {
            if (! $content->isBlob()) {
                continue;
            }
            if (preg_match(self::README_FILE_PATTERN, $content->GetName()) === 1) {
                return $content;
            }
        }

        return null;
    }
}
