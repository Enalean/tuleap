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
}

export interface AddChildrenToSwimlanePayload {
    swimlane: Swimlane;
    children_cards: Card[];
}

export interface ReorderCardsPayload {
    swimlane: Swimlane;
    column: ColumnDefinition;
    position: CardPosition;
}

export interface HandleDropPayload {
    dropped_card: HTMLElement;
    target_cell: HTMLElement;
    source_cell: HTMLElement;
    sibling_card?: HTMLElement;
}
