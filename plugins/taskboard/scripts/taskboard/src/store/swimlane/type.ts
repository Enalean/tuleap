/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { Card, CardPosition, ColumnDefinition, Swimlane } from "../../type";

export interface SwimlaneState {
    swimlanes: Array<Swimlane>;
    is_loading_swimlanes: boolean;
    dropzone_rejecting_drop?: HTMLElement;
    is_card_creation_blocked_due_to_ongoing_creation: boolean;
}

export interface AddChildrenToSwimlanePayload {
    readonly swimlane: Swimlane;
    readonly children_cards: Card[];
}

export interface ReorderCardsPayload {
    readonly swimlane: Swimlane;
    readonly column: ColumnDefinition;
    readonly position: CardPosition;
}

export interface MoveCardsPayload {
    readonly card: Card;
    readonly swimlane: Swimlane;
    readonly column: ColumnDefinition;
    readonly position?: CardPosition;
}

export interface HandleDropPayload {
    readonly dropped_card: HTMLElement;
    readonly target_cell: HTMLElement;
    readonly source_cell: HTMLElement;
    readonly sibling_card?: HTMLElement;
}

export interface HandleDragPayload {
    readonly dropped_card?: Element;
    readonly target_cell?: Element;
    readonly source_cell?: Element;
}

export interface RefreshCardActionPayload {
    readonly swimlane: Swimlane;
    readonly card: Card;
}

export interface RefreshCardMutationPayload {
    readonly swimlane: Swimlane;
    readonly refreshed_card: Card;
}
