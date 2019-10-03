/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { Card, RootState, Swimlane } from "../../type";
import { recursiveGet } from "tlp";
import { ActionContext } from "vuex";
import { SwimlaneState } from "./type";

export async function loadSwimlanes(
    context: ActionContext<SwimlaneState, RootState>
): Promise<void> {
    context.commit("setIsLoadingSwimlanes", true);
    try {
        await recursiveGet(`/api/v1/taskboard/${context.rootState.milestone_id}/cards`, {
            params: {
                limit: 100
            },
            getCollectionCallback: (collection: Card[]): Swimlane[] => {
                const swimlanes = collection.map(card => {
                    return { card, children_cards: [], is_loading_children_cards: false };
                });
                context.commit("addSwimlanes", swimlanes);
                swimlanes
                    .filter(swimlane => swimlane.card.has_children === true)
                    .map(swimlane_with_children =>
                        context.dispatch("loadChildrenCards", swimlane_with_children)
                    );

                return swimlanes;
            }
        });
    } catch (error) {
        await context.dispatch("error/handleErrorMessage", error, { root: true });
    } finally {
        context.commit("setIsLoadingSwimlanes", false);
    }
}

export async function loadChildrenCards(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane
): Promise<void> {
    context.commit("beginLoadingChildren", swimlane);
    try {
        const card_id = swimlane.card.id;
        await recursiveGet(`/api/v1/taskboard_cards/${card_id}/children`, {
            params: {
                milestone_id: context.rootState.milestone_id,
                limit: 100
            },
            getCollectionCallback: (collection: Card[]): Card[] => {
                context.commit("addChildrenToSwimlane", {
                    swimlane,
                    children_cards: collection
                });
                return collection;
            }
        });
    } catch (error) {
        // Show an error modal
    } finally {
        context.commit("endLoadingChildren", swimlane);
    }
}
