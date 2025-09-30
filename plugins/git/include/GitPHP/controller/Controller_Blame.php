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

use Tuleap\Git\Repository\View\LanguageDetectorForPrismJS;
use Tuleap\Git\Unicode\DangerousUnicodeText;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;

/**
 * Blame controller class
 *
 */
class Controller_Blame extends ControllerBase // phpcs:ignore
{
    public function __construct(private readonly BlobDataReader $data_reader)
    {
        parent::__construct();
        if (! $this->project) {
            throw new MessageException(dgettext('gitphp', 'Project is required'), true);
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
    #[\Override]
    protected function GetTemplate() // phpcs:ignore
    {
        return 'tuleap/blame.tpl';
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
    #[\Override]
    public function GetName($local = false) // phpcs:ignore
    {
        if ($local) {
            return dgettext('gitphp', 'blame');
        }
        return 'blame';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    #[\Override]
    protected function ReadQuery() // phpcs:ignore
    {
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        } else {
            $this->params['hashbase'] = 'HEAD';
        }
        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['o']) && ($_GET['o'] == 'js')) {
            $this->params['js'] = true;
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    #[\Override]
    protected function LoadData() // phpcs:ignore
    {
        $head = $this->project->GetHeadCommit();
        if ($head === null) {
            throw new NotFoundException();
        }
        $this->tpl->assign('head', $head);

        $commit = $this->project->GetCommit($this->params['hashbase']);
        if ($commit === null) {
            throw new NotFoundException();
        }
        $this->tpl->assign('commit', $commit);

        if ((! isset($this->params['hash'])) && (isset($this->params['file']))) {
            $this->params['hash'] = $commit->PathToHash($this->params['file']);
        }

        $blob = $this->project->GetBlob($this->params['hash']);
        if (! $blob) {
            throw new NotFoundException();
        }

        if ($this->params['file']) {
            $blob->SetPath($this->params['file']);
        }
        $blob->SetCommit($commit);
        $this->tpl->assign('blob', $blob);

        $this->tpl->assign('blame', $blob->GetBlame());

        if (isset($this->params['js']) && $this->params['js']) {
            return;
        }

        $pathtree = [];
        $path     = dirname($blob->GetPath());
        while ($path !== '.') {
            $name                = basename($path);
            $pathtreepiece       = new \stdClass();
            $pathtreepiece->name = $name;
            $pathtreepiece->path = $path;
            $pathtree[]          = $pathtreepiece;

            $path = dirname($path);
        }
        $this->tpl->assign('pathtree', array_reverse($pathtree));
        $this->tpl->assign('tree', $commit->GetTree());

        $detector = new LanguageDetectorForPrismJS();
        $this->tpl->assign('language', $detector->getLanguage($blob->GetName()));
        $this->tpl->assign(
            'potentially_dangerous_bidirectional_text_warning',
            DangerousUnicodeText::getCodePotentiallyDangerousBidirectionalUnicodeTextWarning($blob->GetData())
        );
        $this->tpl->assign('bloblines', $this->data_reader->getDataLinesInUTF8($blob));
        $core_assets = new \Tuleap\Layout\IncludeCoreAssets();
        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset($core_assets, 'syntax-highlight.js'));
        $git_assets = new IncludeViteAssets(
            __DIR__ . '/../../../scripts/repository/frontend-assets',
            '/assets/git/repository'
        );
        $GLOBALS['Response']->includeFooterJavascriptFile((new JavascriptViteAsset($git_assets, 'src/file/line-highlight.ts'))->getFileURL());
    }
}
