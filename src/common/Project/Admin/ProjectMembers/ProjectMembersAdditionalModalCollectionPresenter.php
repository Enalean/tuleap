<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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


namespace Tuleap\Project\Admin\ProjectMembers;

use CSRFSynchronizerToken;
use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Layout\CssAsset;

class ProjectMembersAdditionalModalCollectionPresenter implements Dispatchable
{
    public const NAME = "project_admin_members_additional_modal";

    /**
     * @var Project
     */
    private $project;
    private $csrf_token;

    public $modals_buttons = array();
    public $modals_content = array();
    /**
     * @var
     */
    private $user_locale;
    /**
     * @var string
     */
    private $javascript_file;
    /**
     * @var CssAsset
     */
    private $css_asset;

    public function __construct(
        Project $project,
        CSRFSynchronizerToken $csrf_token,
        $user_locale
    ) {
        $this->project     = $project;
        $this->csrf_token  = $csrf_token;
        $this->user_locale = $user_locale;
    }

    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param $purified_html_button string A purified html string containing a button meant to trigger a modal.
     */
    public function addModalButton($purified_html_button)
    {
        $this->modals_buttons["purified_html_button"] = $purified_html_button;
    }

    /**
     * @param $purified_html_modal_content string A purified html string containing a modal.
     */
    public function addModalContent($purified_html_modal_content)
    {
        $this->modals_content["purified_html_modal"] = $purified_html_modal_content;
    }

    public function getCSRF()
    {
        return $this->csrf_token;
    }

    public function getCurrentLocale()
    {
        return $this->user_locale;
    }

    public function setJavascriptFile(string $path): void
    {
        $this->javascript_file = $path;
    }

    public function getJavascriptFile(): string
    {
        return $this->javascript_file;
    }

    public function setCssAsset(CssAsset $css_asset): void
    {
        $this->css_asset = $css_asset;
    }

    public function getCssAsset(): CssAsset
    {
        return $this->css_asset;
    }

    public function hasJavascriptFile(): bool
    {
        return $this->javascript_file !== null;
    }

    public function hasCssAsset(): bool
    {
        return $this->css_asset !== null;
    }
}
