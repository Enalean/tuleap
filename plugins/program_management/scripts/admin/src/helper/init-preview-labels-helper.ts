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
import { ElementAdapter } from "../dom/ElementAdapter";
import { TrackerSelector } from "../dom/TrackerSelector";
import { IterationActivator } from "../milestones/IterationActivator";

export const PROGRAM_INCREMENT_LABEL_ID = "admin-configuration-program-increment-label-section";
export const PROGRAM_INCREMENT_SUB_LABEL_ID =
    "admin-configuration-program-increment-sub-label-section";
const PROGRAM_INCREMENTS_ILLUSTRATION_ID =
    "program-management-admin-program-increments-illustration";
export const ITERATIONS_LABEL_ID = "admin-configuration-iteration-label-section";
export const ITERATIONS_SUB_LABEL_ID = "admin-configuration-iteration-sub-label-section";
export const ITERATION_SELECT_ID = "admin-configuration-iteration-tracker";
const ITERATION_ILLUSTRATION_ID = "program-management-admin-iterations-illustration";

export function initPreviewTrackerLabels(
    retriever: RetrieveElement,
    gettext_provider: GettextProvider,
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
        PROGRAM_INCREMENT_SUB_LABEL_ID,
    );
    const program_increments_illustration = ElementAdapter.fromId(
        retriever,
        PROGRAM_INCREMENTS_ILLUSTRATION_ID,
    );
    const program_increments_actualizer = PreviewActualizer.fromContainerAndTimeboxLabels(
        gettext_provider,
        program_increments_illustration,
        program_increment_label,
        program_increment_sub_label,
        gettext_provider.gettext("Program Increments"),
        gettext_provider.gettext("program increment"),
    );

    program_increments_actualizer.initTimeboxPreview();

    const iterations_label = TimeboxLabel.fromId(retriever, ITERATIONS_LABEL_ID);
    const iterations_sub_label = TimeboxLabel.fromId(retriever, ITERATIONS_SUB_LABEL_ID);
    const iteration_selector = TrackerSelector.fromId(retriever, ITERATION_SELECT_ID);
    const iterations_illustration = ElementAdapter.fromId(retriever, ITERATION_ILLUSTRATION_ID);
    const iterations_preview_actualizer = PreviewActualizer.fromContainerAndTimeboxLabels(
        gettext_provider,
        iterations_illustration,
        iterations_label,
        iterations_sub_label,
        gettext_provider.gettext("Iterations"),
        gettext_provider.gettext("iteration"),
    );
    const iteration_activator = new IterationActivator(
        iterations_label,
        iterations_sub_label,
        iteration_selector,
        iterations_preview_actualizer,
    );

    iteration_activator.watchIterationSelection();
}
