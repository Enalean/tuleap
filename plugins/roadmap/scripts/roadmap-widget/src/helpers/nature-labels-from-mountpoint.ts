/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { NaturesLabels } from "../type";
import type { VueGettextProvider } from "./vue-gettext-provider";

export async function parseNatureLabels(
    mount_point: HTMLElement,
    gettext_provider: VueGettextProvider,
): Promise<NaturesLabels> {
    const parsed_data_attribute: Array<{
        readonly shortname: string;
        readonly forward_label: string;
    }> = await JSON.parse(mount_point.dataset.visibleNatures || "[]");

    const nature_labels_including_no_nature = new NaturesLabels([
        ["", gettext_provider.$gettext("Linked to")],
    ]);

    return parsed_data_attribute.reduce(
        (visible_natures: NaturesLabels, { shortname, forward_label }): NaturesLabels => {
            return visible_natures.set(shortname, forward_label);
        },
        nature_labels_including_no_nature,
    );
}
