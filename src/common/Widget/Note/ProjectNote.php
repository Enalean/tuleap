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
use Project;
use TemplateRenderer;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CodeBlockFeaturesInterface;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Project\MappingRegistry;

class ProjectNote extends \Widget
{
    public const NAME = 'projectnote';

    private $content;
    /**
     * @var NoteDao
     */
    private $dao;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    private $title;
    /**
     * @var CodeBlockFeaturesInterface
     */
    private $code_block_features;
    /**
     * @var string | null
     */
    private $interpreted_content = null;

    public function __construct(NoteDao $dao, TemplateRenderer $renderer)
    {
        parent::__construct(self::NAME);

        $this->dao                 = $dao;
        $this->renderer            = $renderer;
        $this->code_block_features = new CodeBlockFeatures();
    }

    public function getTitle()
    {
        if ($this->title !== null) {
            return $this->title;
        }

        return _('Note');
    }

    public function getDescription()
    {
        return _('Allow to write informations for users on your dashboards using Markdown');
    }

    public function getIcon()
    {
        return "fa-sticky-note";
    }

    public function isUnique()
    {
        return false;
    }

    /**
     * @param $id
     */
    public function loadContent($id)
    {
        $row           = $this->dao->get($id);
        $this->title   = $row['title'];
        $this->content = $row['content'];
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences($widget_id)
    {
        return $this->renderer->renderToString(
            'note-preferences',
            new NotePreferencesPresenter($widget_id, $this->title, $this->content)
        );
    }

    public function getInstallPreferences()
    {
        return $this->renderer->renderToString(
            'note-preferences',
            new NotePreferencesPresenter(0, '', '')
        );
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $content_id = $request->getValidated('content_id', 'uint', 0);

        $note = $request->get('note');

        return $this->dao->update($content_id, $note['title'], $note['content']);
    }

    public function create(Codendi_Request $request)
    {
        if ($this->owner_id === null) {
            $current_project = $request->getProject();
            if ($current_project && ! $current_project->isError()) {
                $this->owner_id = $current_project->getID();
            } else {
                return false;
            }
        }

        $note = $request->get('note');

        return (int) $this->dao->create($this->owner_id, $note['title'], $note['content']);
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        return $this->dao->duplicate($new_project->getID(), $id);
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

        if ($this->code_block_features->isMermaidNeeded()) {
            $javascript_dependencies[] = [
                'file' => $this->getAssets()->getFileURL('mermaid.js'),
            ];
        }

        return $javascript_dependencies;
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

    public function getContent(): string
    {
        return $this->interpretContent();
    }

    public function interpretContent(): string
    {
        if ($this->interpreted_content === null) {
            $interpreter = CommonMarkInterpreter::build(
                \Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension($this->code_block_features),
            );

            $this->interpreted_content = $interpreter->getInterpretedContentWithReferences($this->content, (int) $this->owner_id);
        }

        return $this->interpreted_content;
    }

    private function getAssets(): IncludeAssets
    {
        return new \Tuleap\Layout\IncludeCoreAssets();
    }

    public function exportAsXML(): \SimpleXMLElement
    {
        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        $preference = $widget->addChild('preference');
        $preference->addAttribute('name', 'note');

        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->title,
            ['name' => 'title']
        );
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->content,
            ['name' => 'content']
        );

        return $widget;
    }
}
