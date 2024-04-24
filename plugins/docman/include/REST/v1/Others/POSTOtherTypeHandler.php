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

namespace Tuleap\Docman\REST\v1\Others;

use Closure;
use DateTimeImmutable;
use Docman_Folder;
use Docman_Item;
use Docman_PermissionsManager;
use Luracast\Restler\RestException;
use Project;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\REST\v1\CopyItem\CopyItem;
use Tuleap\Docman\REST\v1\CopyItem\DocmanValidateRepresentationForCopy;
use Tuleap\Docman\REST\v1\CreatedItemRepresentation;
use Tuleap\Docman\REST\v1\CreateOtherTypeItem;
use Tuleap\Docman\REST\v1\DocmanFolderPermissionChecker;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\DocmanItemsRequest;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\REST\I18NRestException;

final readonly class POSTOtherTypeHandler
{
    /**
     * @param Closure(Project, class-string<Docman_Item>): CopyItem $get_item_copier
     * @param Closure(Project): CreateOtherTypeItem $get_item_creator
     */
    public function __construct(
        private ItemCanHaveSubItemsChecker $can_have_sub_items_checker,
        private DocmanValidateRepresentationForCopy $representation_for_copy_validation,
        private DocmanItemsEventAdder $event_adder,
        private Closure $get_item_copier,
        private Closure $get_item_creator,
    ) {
    }

    /**
     * @throws RestException 400
     * @throws RestException 404
     */
    public function handle(
        DocmanItemsRequest $item_request,
        DocmanOtherTypePOSTRepresentation $post_representation,
    ): CreatedItemRepresentation {
        $parent = $item_request->getItem();
        $this->can_have_sub_items_checker->checkItemCanHaveSubitems($parent);

        $project = $item_request->getProject();

        $docman_folder_permission_checker = new DocmanFolderPermissionChecker(Docman_PermissionsManager::instance($project->getGroupId()));
        $docman_folder_permission_checker->checkUserCanWriteFolder($item_request->getUser(), (int) $parent->getId());

        $this->addAllEvent($project);

        if ($this->representation_for_copy_validation->isValidAsANonCopyRepresentation($post_representation)) {
            return $this->createItem($project, $parent, $item_request->getUser(), $post_representation);
        }
        if ($this->representation_for_copy_validation->isValidAsACopyRepresentation($post_representation)) {
            return $this->copyItem($item_request, $project, $parent, $post_representation);
        }

        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create an other type document (the properties %s are required for the creation)',
                implode(', ', $post_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    private function addAllEvent(\Project $project): void
    {
        $this->event_adder->addLogEvents();
        $this->event_adder->addNotificationEvents($project);
    }

    /**
     * @throws RestException
     */
    private function createItem(
        Project $project,
        Docman_Folder $parent,
        \PFUser $current_user,
        DocmanOtherTypePOSTRepresentation $post_representation,
    ): CreatedItemRepresentation {
        $docman_item_creator = call_user_func($this->get_item_creator, $project);
        assert($docman_item_creator instanceof CreateOtherTypeItem);
        try {
            return $docman_item_creator->createOtherType(
                $parent,
                $current_user,
                $post_representation,
                new DateTimeImmutable(),
                $project
            );
        } catch (HardCodedMetadataException | CustomMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
            );
        }
    }

    /**
     * @throws RestException
     */
    private function copyItem(
        DocmanItemsRequest $item_request,
        Project $project,
        Docman_Folder $parent,
        DocmanOtherTypePOSTRepresentation $post_representation,
    ): CreatedItemRepresentation {
        $copier = call_user_func($this->get_item_copier, $project, OtherDocument::class);
        assert($copier instanceof CopyItem);

        return $copier->copyItem(
            new DateTimeImmutable(),
            $parent,
            $item_request->getUser(),
            $post_representation->copy
        );
    }
}
