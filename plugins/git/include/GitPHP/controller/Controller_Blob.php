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

use EventManager;
use Tuleap\Git\BinaryDetector;
use Tuleap\Git\CommonMarkExtension\LinkToGitFileBlobFinder;
use Tuleap\Git\CommonMarkExtension\LinkToGitFileExtension;
use Tuleap\Git\GitPHP\Events\DisplayFileContentInGitView;
use Tuleap\Git\Repository\View\LanguageDetectorForPrismJS;
use Tuleap\Git\Unicode\DangerousUnicodeText;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;

/**
 * Blob controller class
 *
 */
class Controller_Blob extends ControllerBase // phpcs:ignore
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
        if (isset($this->params['plain']) && $this->params['plain']) {
            return 'blobplain.tpl';
        }
        return 'tuleap/blob.tpl';
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
            return dgettext('gitphp', 'blob');
        }
        return 'blob';
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
        $this->params['show_source'] = isset($_GET['show_source']);
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    #[\Override]
    protected function LoadHeaders() // phpcs:ignore
    {
        if (isset($this->params['plain']) && $this->params['plain']) {
            if (isset($this->params['file'])) {
                $saveas = $this->params['file'] ?? '';
            } else {
                $saveas = ($this->params['hash'] ?? '') . '.txt';
            }

            $headers = [];

            $mime = null;
            if (Config::GetInstance()->GetValue('filemimetype', true)) {
                if ((! isset($this->params['hash'])) && (isset($this->params['file']))) {
                    $commit = $this->project->GetCommit($this->params['hashbase'] ?? '');
                    if ($commit !== null) {
                        $this->params['hash'] = $commit->PathToHash($this->params['file']);
                    }
                }

                $blob = $this->project->GetBlob($this->params['hash'] ?? '');
                if ($blob === null) {
                    throw new NotFoundException();
                }
                $blob->SetPath($this->params['file']);

                $mime = $blob->FileMime();
            }

            if ($mime) {
                $headers[] = 'Content-type: ' . $mime;
            } else {
                $headers[] = 'Content-type: text/plain; charset=UTF-8';
            }

            $headers[] = 'Content-disposition: attachment; filename="' . self::removeNonASCIICharFromFilenameToBeUsedAsAttachmentHeaderFilename($saveas) . '"';
            $headers[] = 'X-Content-Type-Options: nosniff';

            $this->headers = $headers;
        }
    }

    /**
     * @psalm-taint-escape header
     */
    private static function removeNonASCIICharFromFilenameToBeUsedAsAttachmentHeaderFilename(string $save_as): string
    {
        return str_replace('"', '\"', preg_replace('/[^(\x20-\x7F)]*/', '', $save_as));
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

        if (! empty($this->params['file'])) {
            $blob->SetPath($this->params['file']);
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

        $blob->SetCommit($commit);
        $this->tpl->assign('blob', $blob);

        if (isset($this->params['plain']) && $this->params['plain']) {
            return;
        }

        $head = $this->project->GetHeadCommit();
        $this->tpl->assign('head', $head);

        $this->tpl->assign('tree', $commit->GetTree());

        if (Config::GetInstance()->GetValue('filemimetype', true)) {
            $event = new DisplayFileContentInGitView($this->getTuleapGitRepository(), $blob);
            EventManager::instance()->processEvent($event);

            if ($event->isFileInSpecialFormat()) {
                $this->tpl->assign('is_file_in_special_format', true);
                $this->tpl->assign('special_download_url', $event->getSpecialDownloadUrl());
                return;
            }

            $mime = $blob->FileMime();
            if ($mime) {
                $mimetype = strtok($mime, '/');
                if ($mimetype == 'image') {
                    $this->tpl->assign('datatag', true);
                    $this->tpl->assign('mime', $mime);
                    $this->tpl->assign('data', base64_encode($blob->GetData()));
                    return;
                }
            }
        }

        if (BinaryDetector::isBinary($blob->GetData())) {
            $this->tpl->assign('is_binaryfile', true);
            return;
        }

        $core_assets = new \Tuleap\Layout\IncludeCoreAssets();

        $this->tpl->assign('extrascripts', ['blame']);

        $detector          = new LanguageDetectorForPrismJS();
        $detected_language = $detector->getLanguage($blob->GetName());
        $this->tpl->assign('language', $detected_language);
        $can_file_be_rendered = $detected_language === 'md' || $detected_language === 'markdown';
        $this->tpl->assign('can_be_rendered', $can_file_be_rendered);
        if ($can_file_be_rendered && ! $this->params['show_source']) {
            $code_block_features = new \Tuleap\Markdown\CodeBlockFeatures();
            $content_interpretor = CommonMarkInterpreter::build(
                \Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension($code_block_features),
                new LinkToGitFileExtension(new LinkToGitFileBlobFinder($blob->GetPath(), $commit))
            );
            $this->tpl->assign(
                'rendered_file',
                $content_interpretor->getInterpretedContent(
                    $blob->GetData()
                )
            );
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
        } else {
            $this->tpl->assign(
                'potentially_dangerous_bidirectional_text_warning',
                DangerousUnicodeText::getCodePotentiallyDangerousBidirectionalUnicodeTextWarning($blob->GetData())
            );
            $this->tpl->assign('bloblines', $this->data_reader->getDataLinesInUTF8($blob));
        }
        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset($core_assets, 'syntax-highlight.js'));
        $git_assets = new IncludeViteAssets(
            __DIR__ . '/../../../scripts/repository/frontend-assets',
            '/assets/git/repository'
        );
        $GLOBALS['Response']->includeFooterJavascriptFile((new JavascriptViteAsset($git_assets, 'src/file/line-highlight.ts'))->getFileURL());
    }
}
