/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { AdditionalElement, ItemGroup } from "../elements/toolbar-element";
import { ADDITIONAL_ITEMS_GROUP } from "../elements/toolbar-element";

export const buildAdditionalItemGroup = (additional_element: AdditionalElement): ItemGroup => {
    return {
        name: ADDITIONAL_ITEMS_GROUP,
        elements: [additional_element.item_element],
    };
};

export function buildToolbarItems(
    default_item_positons: ItemGroup[],
    additional_elements: AdditionalElement[],
): ItemGroup[] {
    if (additional_elements.length === 0) {
        return default_item_positons;
    }

    const new_item_postions: ItemGroup[] = default_item_positons;
    additional_elements.forEach((additional_item) => {
        const index = new_item_postions.findIndex(
            (item) => item.name === additional_item.target_name,
        );

        if (additional_item.position === "at_the_start") {
            new_item_postions[index].elements.unshift(additional_item.item_element);
        } else if (additional_item.position === "at_the_end") {
            new_item_postions[index].elements.push(additional_item.item_element);
        } else if (additional_item.position === "before") {
            new_item_postions.splice(index, 0, buildAdditionalItemGroup(additional_item));
        } else {
            new_item_postions.splice(index + 1, 0, buildAdditionalItemGroup(additional_item));
        }
    });
    return new_item_postions;
}
