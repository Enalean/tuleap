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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class FlamingParrot_ContainerPresenter
{
    /** @var array */
    private $breadcrumbs;

    /** @var array */
    private $toolbar;

    /** @var string */
    public $project_name;

    /** @var string */
    public $project_link;

    /**
     * @var ?int
     */
    public $project_id;

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
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_only_one_breadcrumb;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_project_banner = false;
    /**
     * @var \Tuleap\Project\ProjectPrivacyPresenter|null
     * @psalm-readonly
     */
    public $privacy;

    public function __construct(
        array $breadcrumbs,
        $toolbar,
        $project_name,
        $project_link,
        $project_tabs,
        $feedback,
        $feedback_content,
        VersionPresenter $version,
        $sidebar_collapsable,
        ?BannerDisplay $banner,
        PFUser $current_user,
        ?\Tuleap\Project\ProjectPrivacyPresenter $privacy,
        ?Project $project = null
    ) {
        $this->breadcrumbs             = $breadcrumbs;
        $this->has_only_one_breadcrumb = count($breadcrumbs) === 1;

        $this->toolbar             = $toolbar;
        $this->project_name        = $project_name;
        $this->project_link        = $project_link;
        if ($project !== null) {
            $this->project_id = $project->getID();
        }
        $this->project_tabs        = $project_tabs;
        $this->feedback            = $feedback;
        $this->feedback_content    = $feedback_content;
        $this->version             = $version;
        $this->sidebar_collapsable = $sidebar_collapsable;
        $this->privacy             = $privacy;

        if ($banner !== null) {
            $purifier                      = Codendi_HTMLPurifier::instance();
            $this->purified_banner_message = $purifier->purify(
                $banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE
            );
            $this->has_project_banner = true;
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

    public function is_sidebar_collapsable() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->sidebar_collapsable;
    }

    public function sidebar()
    {
        return $this->project_tabs;
    }

    public function has_copyright() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->hasText('global', 'copyright');
    }

    public function copyright()
    {
        return $GLOBALS['Language']->getOverridableText('global', 'copyright');
    }

    public function feedback()
    {
        $html  = $this->feedback->htmlContent();
        $html .= $this->feedback_content;

        return $html;
    }
}
