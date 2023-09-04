/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

import { remove } from "lodash-es";
import { sprintf } from "sprintf-js";
import { SESSION_STORAGE_KEY } from "../session";

export default BacklogItemCollectionService;

BacklogItemCollectionService.$inject = [
    "$q",
    "$window",
    "BacklogItemService",
    "ItemAnimatorService",
    "gettextCatalog",
];

function BacklogItemCollectionService(
    $q,
    $window,
    BacklogItemService,
    ItemAnimatorService,
    gettextCatalog,
) {
    const self = this;
    Object.assign(self, {
        items: {},
        refreshBacklogItem,
        removeBacklogItemsFromCollection,
        addOrReorderBacklogItemsInCollection,
        removeExplicitBacklogElement,
    });

    function refreshBacklogItem(backlog_item_id, options) {
        self.items[backlog_item_id].updating = true;

        if (options && options.did_artifact_links_change) {
            $window.sessionStorage.setItem(
                SESSION_STORAGE_KEY,
                sprintf(gettextCatalog.getString("Successfully updated %(artifact)s"), {
                    artifact: `${self.items[backlog_item_id].short_type} #${backlog_item_id}`,
                }),
            );
            $window.location.reload();
            return $q.when();
        }

        return BacklogItemService.getBacklogItem(backlog_item_id).then(({ backlog_item }) => {
            const {
                background_color_name,
                label,
                initial_effort,
                remaining_effort,
                card_fields,
                status,
                has_children,
                parent,
            } = backlog_item;

            Object.assign(self.items[backlog_item_id], {
                background_color_name,
                label,
                initial_effort,
                remaining_effort,
                card_fields,
                updating: false,
                status,
                has_children,
                parent,
            });

            ItemAnimatorService.animateUpdated(self.items[backlog_item_id]);

            if (!backlog_item.has_children) {
                self.items[backlog_item_id].children.collapsed = true;
            }
        });
    }

    function removeBacklogItemsFromCollection(backlog_items_collection, backlog_items_to_remove) {
        remove(backlog_items_collection, function (item) {
            return backlog_items_to_remove.some((to_remove) => to_remove.id === item.id);
        });
    }

    function addOrReorderBacklogItemsInCollection(
        backlog_items_collection,
        backlog_items_to_add_or_reorder,
        compared_to,
    ) {
        var index = 0;

        self.removeBacklogItemsFromCollection(
            backlog_items_collection,
            backlog_items_to_add_or_reorder,
        );

        if (compared_to) {
            index = backlog_items_collection.findIndex((item) => item.id === compared_to.item_id);

            if (compared_to.direction === "after") {
                index = index + 1;
            }
        }

        var args = [index, 0].concat(backlog_items_to_add_or_reorder);
        Array.prototype.splice.apply(backlog_items_collection, args);
    }

    function removeExplicitBacklogElement(backlog_item) {
        BacklogItemService.removeItemFromExplicitBacklog(backlog_item.project.id, [backlog_item]);
    }
}
