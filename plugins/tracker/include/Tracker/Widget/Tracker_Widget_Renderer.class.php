<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Project\MappingRegistry;

/**
 * Widget_TrackerRenderer
 *
 * Tracker Renderer
 */
abstract class Tracker_Widget_Renderer extends Widget
{
    public $renderer_title;
    public $renderer_id;

    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    public function getTitle()
    {
        return $this->renderer_title ?:
            dgettext('tuleap-tracker', 'Tracker renderer');
    }

    public function getContent()
    {
        $renderer = $this->getRenderer();
        if ($renderer) {
            return $renderer->fetchWidget($this->getCurrentUser());
        }
        $template_renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/widget');
        return $template_renderer->renderToString('renderer-not-found', []);
    }

    private function getRenderer(): ?Tracker_Report_Renderer
    {
        $store_in_session = false;
        $arrf             = Tracker_Report_RendererFactory::instance();
        $renderer         = $arrf->getReportRendererById($this->renderer_id, null, $store_in_session);
        if ($renderer && $renderer->report->getTracker()->userCanView()) {
            return $renderer;
        }
        return null;
    }

    public function isAjax()
    {
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . $purifier->purify($widget_id) . '">
                    ' . $purifier->purify(_('Title')) . '
                </label>
                <input type="text"
                       class="tlp-input"
                       id="title-' . $purifier->purify($widget_id) . '"
                       name="renderer[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="' . $purifier->purify(dgettext('tuleap-tracker', 'Tracker renderer')) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="renderer-id-' . $purifier->purify($widget_id) . '">
                    ' . $purifier->purify(dgettext('tuleap-tracker', 'Renderer id')) . '
                    <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="renderer-id-' . $purifier->purify($widget_id) . '"
                       name="renderer[renderer_id]"
                       value="' . $purifier->purify($this->renderer_id) . '"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-renderer-title">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="widget-renderer-title"
                       name="renderer[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="' . $purifier->purify(dgettext('tuleap-tracker', 'Tracker renderer')) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-renderer-id">
                    ' . $purifier->purify(dgettext('tuleap-tracker', 'Renderer id')) . '
                    <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="widget-renderer-id"
                       name="renderer[renderer_id]"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $sql = "INSERT INTO tracker_widget_renderer (owner_id, owner_type, title, renderer_id)
        SELECT  " . db_ei($owner_id) . ", '" . db_es($owner_type) . "', title, renderer_id
        FROM tracker_widget_renderer
        WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' ";
        $res = db_query($sql);
        return db_insertid($res);
    }

    public function loadContent($id)
    {
        $sql = "SELECT * FROM tracker_widget_renderer WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data                 = db_fetch_array($res);
            $this->renderer_title = $data['title'];
            $this->renderer_id    = $data['renderer_id'];
            $this->content_id     = $id;
        }
    }

    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vId        = new Valid_UInt('renderer_id');
        $vId->setErrorMessage("Can't add empty renderer id");
        $vId->required();
        if ($request->validInArray('renderer', $vId)) {
            $renderer   = $request->get('renderer');
            $sql        = 'INSERT INTO tracker_widget_renderer (owner_id, owner_type, title, renderer_id) VALUES (' . db_ei($this->owner_id) . ", '" . db_es($this->owner_type) . "', '" . db_escape_string($renderer['title']) . "', " . db_escape_int($renderer['renderer_id']) . ")";
            $res        = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $done       = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($renderer = $request->get('renderer')) && $request->valid($vContentId)) {
            $vId = new Valid_UInt('renderer_id');
            if ($request->validInArray('renderer', $vId)) {
                $id = " renderer_id   = " . db_escape_int($renderer['renderer_id']) . " ";
            } else {
                $id = '';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('renderer', $vTitle)) {
                $title = " title = '" . db_escape_string($renderer['title']) . "' ";
            } else {
                $title = '';
            }

            if ($id || $title) {
                $sql  = "UPDATE tracker_widget_renderer SET " . $title . ", " . $id . " WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei((int) $request->get('content_id'));
                $res  = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }

    public function destroy($id)
    {
        $sql = 'DELETE FROM tracker_widget_renderer WHERE id = ' . db_ei($id) . ' AND owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "'";
        db_query($sql);
    }

    public function isUnique()
    {
        return false;
    }

    public function getCategory()
    {
        return dgettext('tuleap-tracker', 'Trackers');
    }

    public function getJavascriptDependencies()
    {
        $renderer = $this->getRenderer();
        if ($renderer === null) {
            return parent::getJavascriptDependencies();
        }
        return $renderer->getJavascriptDependencies();
    }

    public function getStylesheetDependencies()
    {
        $renderer = $this->getRenderer();
        if ($renderer === null) {
            return parent::getStylesheetDependencies();
        }
        return $renderer->getStylesheetDependencies();
    }
}
