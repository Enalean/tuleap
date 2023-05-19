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
* Widget_ImageViewer
*
* Display an image
*
*/
class Widget_ImageViewer extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public ?string $image_title = null;
    public ?string $image_url   = null;
    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    public function getTitle()
    {
        return $this->image_title ?: 'Image';
    }

    public function getContent()
    {
        if (! $this->image_url) {
            return '';
        }

        $hp = Codendi_HTMLPurifier::instance();

        return '<div class="dashboard-widget-imageviewver-content"><img class="dashboard-widget-imageviewver-img"
            src="' . $hp->purify($this->image_url) . '"
            alt="' . $hp->purify($this->getTitle()) . '" /></div>';
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
                <label class="tlp-label" for="title-' . $widget_id . '">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="title-' . $widget_id . '"
                       name="image[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="' . $purifier->purify(_('Image')) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="url-' . $widget_id . '">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="url-' . $widget_id . '"
                       name="image[url]"
                       value="' . $purifier->purify($this->image_url) . '"
                       pattern="https?://.*"
                       title="' . $purifier->purify(_('Please, enter a http:// or https:// link')) . '"
                       required
                       placeholder="https://example.com/image.png">
            </div>
            ';
    }

    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-imageviewer-install-title">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="widget-imageviewer-install-title"
                       name="image[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="' . $purifier->purify(_('Image')) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-imageviewer-install-url">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="widget-imageviewer-install-url"
                       name="image[url]"
                       pattern="https?://.*"
                       title="' . $purifier->purify(_('Please, enter a http:// or https:// link')) . '"
                       required
                       data-test="dashboard-widget-image-input-url"
                       placeholder="https://example.com/image.png">
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
        $da                = CodendiDataAccess::instance();
        $id                = $da->escapeInt($id);
        $template_owner_id = $da->escapeInt($this->owner_id);
        $template_type     = $da->quoteSmart($this->owner_type);
        $owner_id          = $da->escapeInt($owner_id);
        $owner_type        = $da->quoteSmart($owner_type);

        $sql = "INSERT INTO widget_image (owner_id, owner_type, title, url)
                SELECT  $owner_id, $owner_type, title, url
                FROM widget_image
                WHERE id = $id AND owner_id = $template_owner_id AND owner_type = $template_type
        ";
        $res = db_query($sql);
        return db_insertid($res);
    }

    public function loadContent($id)
    {
        $sql = "SELECT * FROM widget_image WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data              = db_fetch_array($res);
            $this->image_title = $data['title'];
            $this->image_url   = $data['url'];
            $this->content_id  = $id;
        }
    }

    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vUrl       = new Valid_HTTPURI('url');
        $vUrl->setErrorMessage($GLOBALS['Language']->getText('widget_imageviewer', 'invalid_url'));
        $vUrl->required();
        if ($request->validInArray('image', $vUrl)) {
            $image  = $request->get('image');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (! $request->validInArray('image', $vTitle)) {
                $image['title'] = 'Image';
            }
            $sql        = 'INSERT INTO widget_image (owner_id, owner_type, title, url) VALUES (' . db_ei($this->owner_id) . ", '" . db_es($this->owner_type) . "', '" . db_escape_string($image['title']) . "', '" . db_escape_string($image['url']) . "')";
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
        if (($image = $request->get('image')) && $request->valid($vContentId)) {
            $vUrl = new Valid_String('url');
            if ($request->validInArray('image', $vUrl)) {
                $url = " url   = '" . db_escape_string($image['url']) . "' ";
            } else {
                $url = '';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('image', $vTitle)) {
                $title = " title = '" . db_escape_string($image['title']) . "' ";
            } else {
                $title = '';
            }

            if ($url || $title) {
                $sql  = "UPDATE widget_image SET " . $title . ", " . $url . " WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei($request->get('content_id'));
                $res  = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }

    public function destroy($id)
    {
        $sql = 'DELETE FROM widget_image WHERE id = ' . db_ei($id) . ' AND owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "'";
        db_query($sql);
    }

    public function isUnique()
    {
        return false;
    }
}
