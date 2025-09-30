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
use Tuleap\Project\Sidebar\ProjectContextPresenter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class FlamingParrot_ContainerPresenter
{
    /** @var array */
    private $breadcrumbs;

    /** @var array */
    private $toolbar;

    /**
     * @var ?int
     */
    public $project_id;

    /** @var Feedback */
    private $feedback;

    /** @var string */
    private $feedback_content;

    /**
     * @var VersionPresenter
     */
    public $version;

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
     * @var ProjectContextPresenter|null
     * @psalm-readonly
     */
    public $project_context;
    /**
     * @var \Tuleap\User\SwitchToPresenter|null
     * @psalm-readonly
     */
    public $switch_to;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_legacy_logo_customized;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_svg_logo_customized;
    /**
     * @var string
     * @psalm-readonly
     */
    public $main_classes;

    public function __construct(
        array $breadcrumbs,
        $toolbar,
        $feedback,
        $feedback_content,
        VersionPresenter $version,
        PFUser $current_user,
        ?ProjectContextPresenter $project_context,
        ?\Tuleap\User\SwitchToPresenter $switch_to,
        bool $is_legacy_logo_customized,
        bool $is_svg_logo_customized,
        array $main_classes,
    ) {
        $this->breadcrumbs               = $breadcrumbs;
        $this->has_only_one_breadcrumb   = count($breadcrumbs) === 1;
        $this->toolbar                   = $toolbar;
        $this->feedback                  = $feedback;
        $this->feedback_content          = $feedback_content;
        $this->version                   = $version;
        $this->current_user_id           = $current_user->getId();
        $this->project_context           = $project_context;
        $this->switch_to                 = $switch_to;
        $this->is_legacy_logo_customized = $is_legacy_logo_customized;
        $this->is_svg_logo_customized    = $is_svg_logo_customized;
        $this->main_classes              = implode(' ', $main_classes);
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
