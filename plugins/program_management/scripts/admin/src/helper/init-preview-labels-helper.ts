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
import type { RetrieveElement } from "../dom/RetrieveElement";
import { PreviewActualizer } from "../milestones/PreviewActualizer";
import type { RetrieveNode } from "../dom/RetrieveNode";

const PROGRAM_INCREMENT_LABEL_ID = "admin-configuration-program-increment-label-section";
const PROGRAM_INCREMENT_SUB_LABEL_ID = "admin-configuration-program-increment-sub-label-section";

export function initPreviewTrackerLabels(
    retriever: RetrieveElement & RetrieveNode,
    gettext_provider: GettextProvider
): void {
    let program_increment_label: TimeboxLabel;
    try {
        program_increment_label = TimeboxLabel.fromId(retriever, PROGRAM_INCREMENT_LABEL_ID);
    } catch (e) {
        // There is no program increment label element when Teams have not yet been configured
        return;
    }
    const program_increment_sub_label = TimeboxLabel.fromId(
        retriever,
        PROGRAM_INCREMENT_SUB_LABEL_ID
    );
    const program_increments_actualizer = PreviewActualizer.fromTimeboxLabels(
        gettext_provider,
        retriever,
        program_increment_label,
        program_increment_sub_label,
        gettext_provider.gettext("Program Increments"),
        gettext_provider.gettext("program increment")
    );

    program_increments_actualizer.initTimeboxPreview();
}
