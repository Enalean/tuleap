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
import type { HandleDragPayload } from "../type";
import type { SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";

export interface FeatureToPlan {
    id: number;
}

export interface FeatureIdToMoveFromProgramIncrementToAnother {
    feature_id: number;
    from_program_increment: ProgramIncrement;
    to_program_increment: ProgramIncrement;
}

export interface FeatureIdWithProgramIncrement {
    feature_id: number;
    program_increment: ProgramIncrement;
}

export interface HandleDropContextWithProgramId extends SuccessfulDropCallbackParameter {
    program_id: number;
}

export function isContainer(element: HTMLElement): boolean {
    return Boolean(element.dataset.isContainer);
}

export function canMove(element: HTMLElement): boolean {
    return element.draggable;
}

export function invalid(handle: HTMLElement): boolean {
    return Boolean(handle.closest("[data-not-drag-handle]"));
}

export function isConsideredInDropzone(child: Element): boolean {
    return child.hasAttribute("draggable");
}

export function checkAcceptsDrop(payload: HandleDragPayload): boolean {
    if (
        !(payload.dropped_card instanceof HTMLElement) ||
        !(payload.target_cell instanceof HTMLElement) ||
        !(payload.source_cell instanceof HTMLElement)
    ) {
        return false;
    }

    const user_can_plan = Boolean(payload.target_cell.dataset.canPlan);
    if (!user_can_plan) {
        const can_not_drop_message = payload.target_cell.getElementsByClassName(
            "drop-not-accepted-overlay"
        );

        if (!can_not_drop_message || !can_not_drop_message[0]) {
            return user_can_plan;
        }

        can_not_drop_message[0].classList.remove("drop-accepted");
        can_not_drop_message[0].classList.add("drop-not-accepted");
    }

    return user_can_plan;
}

export function checkAfterDrag(): void {
    const error_messages = document.getElementsByClassName("drop-not-accepted-overlay");

    [].forEach.call(error_messages, function (dom_message: HTMLElement) {
        dom_message.classList.remove("drop-not-accepted");
        dom_message.classList.add("drop-accepted");
    });
}
