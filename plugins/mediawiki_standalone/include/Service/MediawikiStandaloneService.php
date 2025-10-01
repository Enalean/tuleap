<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsDao;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationStatus;
use Tuleap\MediawikiStandalone\Permissions\Admin\AdminPermissionsController;
use Tuleap\Project\Service\ServiceForCreation;

class MediawikiStandaloneService extends \Service implements ServiceForCreation
{
    public const string ICON_NAME           = 'fas fa-tlp-mediawiki';
    private const string SERVICE_URL_PREFIX = '/mediawiki/';
    public const  string SERVICE_SHORTNAME  = 'plugin_mediawiki_standalone';

    #[\Override]
    public static function forServiceCreation(\Project $project): self
    {
        return new self(
            $project,
            [
                'service_id' => self::FAKE_ID_FOR_CREATION,
                'group_id' => $project->getID(),
                'label' => 'MediaWiki',
                'description' => '',
                'short_name' => self::SERVICE_SHORTNAME,
                'link' => '#',
                'is_active' => 1,
                'is_used' => 0,
                'scope' => self::SCOPE_SYSTEM,
                'rank' => 161,
                'location' => '',
                'server_id' => null,
                'is_in_iframe' => 0,
                'is_in_new_tab' => false,
                'icon' => self::ICON_NAME,
            ],
        );
    }

    #[\Override]
    public function getIconName(): string
    {
        return 'fas fa-tlp-mediawiki';
    }

    #[\Override]
    public function getInternationalizedName(): string
    {
        return 'MediaWiki';
    }

    #[\Override]
    public function getProjectAdministrationName(): string
    {
        return dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone');
    }

    #[\Override]
    public function getInternationalizedDescription(): string
    {
        return $this->getProjectAdministrationName();
    }

    #[\Override]
    public function getUrl(?string $url = null): string
    {
        $dao = new OngoingInitializationsDao(new MediawikiFlavorUsageDao());

        return match ($dao->getStatus($this->project)) {
            OngoingInitializationStatus::InError, OngoingInitializationStatus::Ongoing => '/mediawiki_standalone/under-construction/' . urlencode($this->project->getUnixNameMixedCase()),
            OngoingInitializationStatus::None => self::SERVICE_URL_PREFIX . $this->project->getUnixNameLowerCase(),
        };
    }

    /**
     * @psalm-param non-empty-string $pagename
     */
    public function getPageUrl(string $pagename): string
    {
        $dao = new OngoingInitializationsDao(new MediawikiFlavorUsageDao());

        return match ($dao->getStatus($this->project)) {
            OngoingInitializationStatus::InError, OngoingInitializationStatus::Ongoing => '/mediawiki_standalone/under-construction/' . urlencode($this->project->getUnixNameMixedCase()),
            OngoingInitializationStatus::None => self::SERVICE_URL_PREFIX . $this->project->getUnixNameLowerCase() . '/' . urlencode($pagename),
        };
    }

    #[\Override]
    public function urlCanChange(): bool
    {
        return false;
    }

    private function getBreadCrumbs(\PFUser $user): BreadCrumbCollection
    {
        $crumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki'),
                $this->getUrl()
            )
        );

        if ($user->isAdmin((int) $this->project->getID())) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                dgettext('tuleap-mediawiki_standalone', 'Administration'),
                                AdminPermissionsController::getAdminUrl($this->project)
                            ),
                        ]
                    )
                )
            );
            $crumb->setSubItems($sub_items);
        }

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($crumb);

        return $breadcrumbs;
    }

    public function displayAdministrationHeader(\PFUser $user): void
    {
        $this->displayHeader(
            dgettext('tuleap-mediawiki_standalone', 'MediaWiki administration'),
            $this->getBreadCrumbs($user),
            []
        );
    }

    public function displayMediawikiHeader(\PFUser $user): void
    {
        $this->displayHeader(
            dgettext('tuleap-mediawiki_standalone', 'MediaWiki'),
            $this->getBreadCrumbs($user),
            []
        );
    }
}
