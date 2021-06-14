<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use Codendi_HTMLPurifier;
use Project;
use Tuleap\Project\Admin\Access\ProjectAdministrationLinkPresenter;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Flags\ProjectFlagPresenter;

/**
 * @psalm-immutable
 */
class ProjectContextPresenter
{
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var ProjectPrivacyPresenter
     */
    public $privacy;
    /**
     * @var ProjectFlagPresenter[]
     */
    public $project_flags;
    /**
     * @var bool
     */
    public $has_project_banner;
    /**
     * @var bool
     */
    public $project_banner_is_visible;
    /**
     * @var string
     */
    public $purified_banner;
    /**
     * @var false|string
     */
    public $json_encoded_project_flags;
    /**
     * @var int
     */
    public $nb_project_flags;
    /**
     * @var bool
     */
    public $has_project_flags;
    public bool $has_administration_link;
    public string $administration_link;

    /**
     * @param ProjectFlagPresenter[] $project_flags
     */
    private function __construct(
        Project $project,
        ProjectPrivacyPresenter $privacy,
        ?ProjectAdministrationLinkPresenter $administration_link_presenter,
        array $project_flags,
        ?BannerDisplay $banner,
        string $purified_banner
    ) {
        $this->project_id                 = $project->getID();
        $this->privacy                    = $privacy;
        $this->project_flags              = $project_flags;
        $this->nb_project_flags           = count($project_flags);
        $this->has_project_flags          = $this->nb_project_flags > 0;
        $this->has_project_banner         = $banner !== null;
        $this->project_banner_is_visible  = $banner && $banner->isVisible();
        $this->has_administration_link    = $administration_link_presenter !== null;
        $this->administration_link        = $administration_link_presenter->uri ?? '';
        $this->purified_banner            = $purified_banner;
        $this->json_encoded_project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
    }

    public static function build(
        Project $project,
        ProjectPrivacyPresenter $privacy,
        ?ProjectAdministrationLinkPresenter $administration_link,
        array $project_flags,
        ?BannerDisplay $banner
    ): self {
        $purified_banner = "";
        if ($banner) {
            $purified_banner = Codendi_HTMLPurifier::instance()->purify(
                $banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE
            );
        }

        return new self($project, $privacy, $administration_link, $project_flags, $banner, $purified_banner);
    }
}
