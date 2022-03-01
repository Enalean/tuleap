<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\View\Admin;

use Docman_View_Admin_FilenamePattern;
use DocmanPlugin;
use Tuleap\Docman\View\DocmanViewURLBuilder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Request\NotFoundException;

abstract class AdminView
{
    public function display(array $params): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment('docman');
        $project = $this->getProjectFromParams($params);
        if (! $project || $project->isError() || ! $project->isActive()) {
            throw new NotFoundException();
        }

        $service = $project->getService('docman');
        if (! $service) {
            throw new NotFoundException();
        }

        $user = $this->getUserFromParams($params);
        if (! $user || $user->isAnonymous()) {
            throw new NotFoundException();
        }

        if (! $this->userCanAdmin($user, $project)) {
            throw new NotFoundException();
        }

        $default_url = $params['default_url'] ?? "";

        $documents_link = new BreadCrumbLink(dgettext('tuleap-docman', 'Documents'), $default_url);
        $documents_link->setDataAttribute('test', 'project-documentation');

        $documents_crumb = new BreadCrumb($documents_link);
        $sub_items       = new BreadCrumbSubItems();

        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection([
                    new BreadCrumbLink(
                        dgettext('tuleap-docman', 'Administration'),
                        DocmanViewURLBuilder::buildUrl(
                            $default_url,
                            ['action' => 'admin'],
                            false,
                        ),
                    ),
                ])
            )
        );
        $documents_crumb->setSubItems($sub_items);

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($documents_crumb);

        $include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/docman/',
            '/assets/docman'
        );
        $this->includeStylesheets($include_assets);
        $this->includeJavascript($include_assets);

        $service->displayHeader(
            $this->getTitle($params) . ' - ' . dgettext('tuleap-docman', 'Documents administration'),
            $breadcrumbs,
            []
        );

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates');

        $template = $this->isBurningParrotCompatiblePage() ? 'admin-header-bp' : 'admin-header-fp';

        $renderer->renderToPage($template, [
            'title'      => dgettext('tuleap-docman', 'Administration'),
            'tabs'       => $this->getTabs($default_url),
            'extra_tabs' => $this->getExtraTabs($default_url, $project),
        ]);

        echo '<div class="docman-content">';
        $this->displayContent($renderer, $params);
        echo '</div>';

        $GLOBALS['Response']->footer($params);
    }

    abstract protected function getIdentifier(): string;

    abstract protected function getTitle(array $params): string;

    abstract protected function displayContent(\TemplateRenderer $renderer, array $params): void;

    protected function isBurningParrotCompatiblePage(): bool
    {
        return false;
    }

    protected function includeStylesheets(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
    }

    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
    }

    private function getProjectIdFromParams(array $params): int
    {
        if (! isset($params['group_id'])) {
            return 0;
        }

        return (int) $params['group_id'];
    }

    private function getProjectFromParams(array $params): \Project|null
    {
        $project = null;

        $project_id = $this->getProjectIdFromParams($params);
        if ($project_id > 0) {
            $project = \ProjectManager::instance()->getProject($project_id);
        }

        return $project;
    }

    private function getUserFromParams(array $params): \PFUser|null
    {
        return $params['user'] ?? null;
    }

    private function userCanAdmin(\PFUser $user, \Project $project): bool
    {
        $perms_manager = \Docman_PermissionsManager::instance((int) $project->getID());

        return $perms_manager->userCanAdmin($user);
    }

    /**
     * @return array[]
     */
    private function getTabs(string $default_url): array
    {
        return [
            [
                'title'       => \Docman_View_Admin_Permissions::getTabTitle(),
                'description' => \Docman_View_Admin_Permissions::getTabDescription(),
                'url'         => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_Permissions::IDENTIFIER],
                    false,
                ),
                'is_active'   => $this->getIdentifier() === \Docman_View_Admin_Permissions::IDENTIFIER,
            ],
            [
                'title'       => \Docman_View_Admin_Metadata::getTabTitle(),
                'description' => \Docman_View_Admin_Metadata::getTabDescription(),
                'url'         => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_Metadata::IDENTIFIER],
                    false,
                ),
                'is_active'   => in_array(
                    $this->getIdentifier(),
                    [
                        \Docman_View_Admin_Metadata::IDENTIFIER,
                        \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                        \Docman_View_Admin_MetadataDetailsUpdateLove::IDENTIFIER,
                        \Docman_View_Admin_MetadataImport::IDENTIFIER,
                    ],
                    true
                ),
            ],
            [
                'title'       => \Docman_View_Admin_Obsolete::getTabTitle(),
                'description' => \Docman_View_Admin_Obsolete::getTabDescription(),
                'url'         => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_Obsolete::IDENTIFIER],
                    false,
                ),
                'is_active'   => $this->getIdentifier() === \Docman_View_Admin_Obsolete::IDENTIFIER,
            ],
            [
                'title'       => \Docman_View_Admin_LockInfos::getTabTitle(),
                'description' => \Docman_View_Admin_LockInfos::getTabDescription(),
                'url'         => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_LockInfos::IDENTIFIER],
                    false,
                ),
                'is_active'   => $this->getIdentifier() === \Docman_View_Admin_LockInfos::IDENTIFIER,
            ],
        ];
    }

    /**
     * @return array{is_active: bool, tabs: array{array{description: string, title: string, url: string}}}
     */
    private function getExtraTabs(string $default_url, \Project $project): array
    {
        $interface = \EventManager::instance()->dispatch(new DetectEnhancementOfDocmanInterface($project));

        $tab = [
            [
                'title' => $interface->isEnhanced()
                    ? \Docman_View_Admin_View::getTabTitleWhenInterfaceIsEnhanced()
                    : \Docman_View_Admin_View::getTabTitle(),
                'description' => \Docman_View_Admin_View::getTabDescription(),
                'url' => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_View::IDENTIFIER],
                    false,
                ),
            ],
        ];
        if ((int) \ForgeConfig::getFeatureFlag(DocmanPlugin::PLUGIN_DOCMAN_APPLY_NAMING_PATTERN) === 1) {
            $tab[] = [
                'title' => Docman_View_Admin_FilenamePattern::getTabTitle(),
                'description' => Docman_View_Admin_FilenamePattern::getTabDescription(),
                'url' => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    ['action' => \Docman_View_Admin_FilenamePattern::IDENTIFIER],
                    false,
                ),
            ];
        }

        return [
            'is_active' => $this->getIdentifier() === \Docman_View_Admin_View::IDENTIFIER,
            'tabs' => $tab,
        ];
    }
}
