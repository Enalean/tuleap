<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Michelf\MarkdownExtra;
use Project;
use TemplateRenderer;
use Tuleap\Markdown\CommonMarkInterpreter;

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

    public function __construct(NoteDao $dao, TemplateRenderer $renderer)
    {
        parent::__construct(self::NAME);
        $this->dao      = $dao;
        $this->renderer = $renderer;
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
        $row = $this->dao->get($id);
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
        $owner_type
    ) {
        return $this->dao->duplicate($new_project->getID(), $id);
    }

    public function getContent()
    {
        return CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())->getInterpretedContent($this->content);
        return \Codendi_HTMLPurifier::instance()->purify(MarkdownExtra::defaultTransform($this->content), CODENDI_PURIFIER_FULL);
    }
}
