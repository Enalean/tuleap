<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Project\Banner\BannerDisplay;

class FlamingParrot_ContainerPresenter
{
    /** @var array */
    private $breadcrumbs;

    /** @var array */
    private $toolbar;

    /** @var string */
    private $project_name;

    /** @var string */
    private $project_link;

    /**
     * @var ?int
     */
    public $project_id;

    /** @var bool */
    public $project_is_public;

    /** @var string */
    private $project_privacy;

    /** @var string */
    private $project_tabs;

    /** @var Feedback */
    private $feedback;

    /** @var string */
    private $feedback_content;

    /**
     * @var VersionPresenter
     */
    public $version;

    /** @var bool */
    private $sidebar_collapsable;
    /**
     * @var bool
     */
    public $are_restricted_users_allowed;
    /**
     * @var bool
     */
    public $project_is_public_incl_restricted;
    /**
     * @var bool
     */
    public $project_is_private;
    /**
     * @var bool
     */
    public $project_is_private_incl_restricted;
    /**
     * @var string
     */
    public $purified_banner_message = '';

    /**
     * @var bool
     */
    public $project_banner_is_visible = false;
    /**
     * @var int
     */
    public $current_user_id;

    public function __construct(
        array $breadcrumbs,
        $toolbar,
        $project_name,
        $project_link,
        $project_privacy,
        $project_tabs,
        $feedback,
        $feedback_content,
        VersionPresenter $version,
        $sidebar_collapsable,
        ?BannerDisplay $banner,
        PFUser $current_user,
        ?Project $project = null
    ) {
        $this->breadcrumbs         = $breadcrumbs;
        $this->toolbar             = $toolbar;
        $this->project_name        = $project_name;
        $this->project_link        = $project_link;
        if ($project !== null) {
            $this->project_is_public = $project->isPublic();
            $this->project_id        = $project->getID();
        }
        $this->project_privacy     = $project_privacy;
        $this->project_tabs        = $project_tabs;
        $this->feedback            = $feedback;
        $this->feedback_content    = $feedback_content;
        $this->version             = $version;
        $this->sidebar_collapsable = $sidebar_collapsable;

        $this->are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        if ($this->are_restricted_users_allowed && $project !== null) {
            $this->project_is_public                  = $project->getAccess() === Project::ACCESS_PUBLIC;
            $this->project_is_public_incl_restricted  = $project->getAccess() === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->project_is_private                 = $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            $this->project_is_private_incl_restricted = $project->getAccess() === Project::ACCESS_PRIVATE;
        }

        if ($banner !== null) {
            $purifier                      = Codendi_HTMLPurifier::instance();
            $this->purified_banner_message = $purifier->purify(
                $banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE
            );
            $this->project_banner_is_visible = $banner->isVisible();
        }
        $this->current_user_id = $current_user->getId();
    }

    public function hasBreadcrumbs()
    {
        return count($this->breadcrumbs) > 0;
    }

    public function breadcrumbs()
    {
        return $this->breadcrumbs;
    }

    public function hasToolbar()
    {
        return (count($this->toolbar) > 0);
    }

    public function toolbar()
    {
        return implode('</li><li>', $this->toolbar);
    }

    public function hasSidebar()
    {
        return isset($this->project_tabs);
    }

    public function is_sidebar_collapsable()
    {
        return $this->sidebar_collapsable;
    }

    public function sidebar()
    {
        return $this->project_tabs;
    }

    public function has_copyright()
    {
        return $GLOBALS['Language']->hasText('global', 'copyright');
    }

    public function copyright()
    {
        return $GLOBALS['Language']->getOverridableText('global', 'copyright');
    }

    public function projectName()
    {
        return $this->project_name;
    }

    public function projectLink()
    {
        return $this->project_link;
    }

    public function project_privacy()
    {
        return $this->project_privacy;
    }

    public function feedback()
    {
        $html  = $this->feedback->htmlContent();
        $html .= $this->feedback_content;

        return $html;
    }
}
