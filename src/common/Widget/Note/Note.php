<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Widget\Note;

use Codendi_Request;
use TemplateRenderer;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;

abstract class Note extends \Widget
{
    protected ?string $title             = null;
    protected ?string $content           = null;
    private ?string $interpreted_content = null;
    protected NoteDao $dao;
    private TemplateRenderer $renderer;
    private CodeBlockFeatures $code_block_features;

    public function __construct(NoteDao $dao, TemplateRenderer $renderer)
    {
        parent::__construct(static::getName());
        $this->dao                 = $dao;
        $this->renderer            = $renderer;
        $this->code_block_features = new CodeBlockFeatures();
    }

    abstract protected static function getName(): string;

    public function getTitle(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }
        return _('Note');
    }

    public function getIcon(): string
    {
        return "fa-sticky-note";
    }

    public function isUnique(): bool
    {
        return false;
    }

    /**
     * @param string $id
     */
    public function loadContent($id): void
    {
        $row           = $this->dao->get($id);
        $this->title   = $row['title'];
        $this->content = $row['content'];
    }

    /**
     * @param string $widget_id
     */
    public function hasPreferences($widget_id): bool
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        return $this->renderer->renderToString(
            'note-preferences',
            new NotePreferencesPresenter($widget_id, $this->title, $this->content)
        );
    }

    public function getInstallPreferences(): string
    {
        return $this->renderer->renderToString(
            'note-preferences',
            new NotePreferencesPresenter(0, '', '')
        );
    }

    public function updatePreferences(Codendi_Request $request): bool
    {
        $content_id = $request->getValidated('content_id', 'uint', 0);

        $note = $request->get('note');

        return $this->dao->update($content_id, $note['title'], $note['content']);
    }

    protected function createNote(Codendi_Request $request, int $owner_id, string $owner_type): int
    {
        $note = $request->get('note');

        return (int) $this->dao->create($owner_id, $owner_type, $note['title'], $note['content']);
    }

    public function getContent(): string
    {
        return $this->interpretContent();
    }

    public function interpretContent(): string
    {
        if ($this->content === null) {
            return '';
        }

        if ($this->interpreted_content === null) {
            $interpreter = CommonMarkInterpreter::build(
                \Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension($this->code_block_features),
            );

            $this->interpreted_content = $interpreter->getInterpretedContentWithReferences($this->content, (int) $this->owner_id);
        }

        return $this->interpreted_content;
    }

    /** @return array */
    public function getJavascriptDependencies()
    {
        $javascript_dependencies = [];

        $this->interpretContent();
        if ($this->code_block_features->isSyntaxHighlightNeeded()) {
            $javascript_dependencies[] = [
                'file' => $this->getAssets()->getFileURL('syntax-highlight.js'),
            ];
        }

        return $javascript_dependencies;
    }

    public function getJavascriptAssets(): array
    {
        if ($this->code_block_features->isMermaidNeeded()) {
            $js_asset = new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../../../scripts/mermaid-diagram-element/frontend-assets',
                    '/assets/core/mermaid-diagram-element',
                ),
                'src/index.ts',
            );
            return [$js_asset];
        }
        return [];
    }

    /**
     * @return CssAssetCollection
     */
    public function getStylesheetDependencies()
    {
        $this->interpretContent();

        if ($this->code_block_features->isSyntaxHighlightNeeded()) {
            return new CssAssetCollection(
                [
                    new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'syntax-highlight'),
                ]
            );
        }

        return new CssAssetCollection([]);
    }

    private function getAssets(): IncludeAssets
    {
        return new \Tuleap\Layout\IncludeCoreAssets();
    }
}
