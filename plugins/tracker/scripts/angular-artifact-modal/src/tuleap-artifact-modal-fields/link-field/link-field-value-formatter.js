/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

export function formatLinkFieldValue(field_value) {
    if (field_value === undefined) {
        return null;
    }

    return buildLinks(field_value);
}

function buildLinks(field) {
    let { field_id, links: all_links } = field;
    // Merge the text field with the selectbox to create the list of links
    if (typeof field.unformatted_links === "string") {
        const ids = field.unformatted_links.split(",");
        const id_objects = ids.map((link_id) => {
            return { id: Number.parseInt(link_id, 10) };
        });
        all_links = all_links.concat(id_objects);
    }
    // Then, filter out all the invalid id values (null, undefined, etc)
    const links = all_links.filter((link) => Boolean(link.id));

    return { field_id, links };
}
