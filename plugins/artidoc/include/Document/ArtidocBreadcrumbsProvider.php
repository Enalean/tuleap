<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use Docman_ItemFactory;
use Docman_PermissionsManager;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\EllipsisBreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

final readonly class ArtidocBreadcrumbsProvider
{
    private const MAX_NB_ITEMS = 5;

    public function __construct(private Docman_ItemFactory $item_factory)
    {
    }

    public function getBreadcrumbs(ArtidocWithContext $document_information, \PFUser $user): BreadCrumbCollection
    {
        $service = $document_information->getContext(ServiceDocman::class);
        if (! $service instanceof ServiceDocman) {
            throw new \LogicException('Service is missing');
        }

        $collection = new BreadCrumbCollection();
        $collection->addBreadCrumb($this->getRootBreadCrumb($service, $user));

        $hierarchy = $this->getParentsUntilDocumentBreadCrumbs($document_information->document, $service);
        foreach ($hierarchy as $child) {
            $collection->addBreadCrumb($child);
        }

        return $collection;
    }

    private function getRootBreadCrumb(ServiceDocman $service, \PFUser $user): BreadCrumb
    {
        $project_id = (int) $service->getProject()->getId();

        $breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-artidoc', 'Documents'),
                $service->getUrl(),
            )
        );

        $permissions_manager = Docman_PermissionsManager::instance($project_id);
        if ($permissions_manager->userCanAdmin($user)) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                dgettext('tuleap-artidoc', 'Administration'),
                                '/plugins/docman/?' . http_build_query(
                                    [
                                        'group_id' => $project_id,
                                        'action'   => 'admin',
                                    ]
                                ),
                            ),
                        ]
                    )
                )
            );
            $breadcrumb->setSubItems($sub_items);
        }
        return $breadcrumb;
    }

    /**
     * @return array<BreadCrumb|EllipsisBreadCrumb>
     */
    private function getParentsUntilDocumentBreadCrumbs(
        Artidoc $document,
        ServiceDocman $service,
    ): array {
        $hierarchy = [];
        if ($document->getParentId()) {
            $hierarchy[] = new BreadCrumb(
                new BreadCrumbLink(
                    $document->getTitle(),
                    '/artidoc/' . $document->getId() . '/',
                ),
            );

            $parent = $this->item_factory->getItemFromDb($document->getParentId());
            $nb     = 1;
            while ($parent && $parent->getParentId() !== 0) {
                if ($nb++ >= self::MAX_NB_ITEMS) {
                    $hierarchy[] = new EllipsisBreadCrumb(
                        dgettext('tuleap-artidoc', 'Parent folders are not displayed to not clutter the interface'),
                    );
                    break;
                }

                $hierarchy[] = new BreadCrumb(
                    new BreadCrumbLink(
                        $parent->getTitle(),
                        $service->getUrl() . 'folder/' . $parent->getId(),
                    ),
                );

                $parent = $this->item_factory->getItemFromDb($parent->getParentId());
            }
        }
        return array_reverse($hierarchy);
    }
}
