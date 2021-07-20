/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { GettextProvider } from "../GettextProvider";
import { TimeboxLabel } from "../dom/TimeboxLabel";
import { initPreview } from "../milestones/preview-actualizer";
import { PROGRAM_INCREMENT_LABEL_ID, PROGRAM_INCREMENT_SUB_LABEL_ID } from "../index";
import type { RetrieveElement } from "../dom/RetrieveElement";

export function initPreviewTrackerLabels(
    doc: Document,
    gettext_provider: GettextProvider,
    retriever: RetrieveElement
): void {
    const program_increment_label_element = doc.getElementById(PROGRAM_INCREMENT_LABEL_ID);

    if (
        !program_increment_label_element ||
        !(program_increment_label_element instanceof HTMLInputElement)
    ) {
        return;
    }

    const program_increment_label = TimeboxLabel.fromId(retriever, PROGRAM_INCREMENT_LABEL_ID);
    const program_increment_sub_label = TimeboxLabel.fromId(
        retriever,
        PROGRAM_INCREMENT_SUB_LABEL_ID
    );

    initPreview(retriever, gettext_provider, program_increment_label, program_increment_sub_label);
}
