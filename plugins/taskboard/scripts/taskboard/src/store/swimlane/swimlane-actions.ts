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

import type { Card, Swimlane } from "../../type";
import { get, recursiveGet } from "@tuleap/tlp-fetch";
import type { ActionContext } from "vuex";
import type { RefreshCardActionPayload, SwimlaneState } from "./type";
import type { RootState } from "../type";
import type { UserPreference, UserPreferenceValue } from "../user/type";
import { injectDefaultPropertiesInCard } from "../../helpers/card-default";

export * from "./drag-drop-actions";
export * from "./card/card-actions";

export async function loadSwimlanes(
    context: ActionContext<SwimlaneState, RootState>,
): Promise<void> {
    context.commit("beginLoadingSwimlanes");
    try {
        await recursiveGet(`/api/v1/taskboard/${context.rootState.milestone_id}/cards`, {
            params: {
                limit: 100,
            },
            getCollectionCallback: (collection: Card[]): Swimlane[] => {
                const swimlanes = collection.map((card: Card): Swimlane => {
                    injectDefaultPropertiesInCard(card);
                    return {
                        card,
                        children_cards: [],
                        is_loading_children_cards: false,
                    };
                });
                context.commit("addSwimlanes", swimlanes);
                swimlanes
                    .filter((swimlane) => swimlane.card.has_children)
                    .map((swimlane_with_children) =>
                        context.dispatch("loadChildrenCards", swimlane_with_children),
                    );

                return swimlanes;
            },
        });
    } catch (error) {
        await context.dispatch("error/handleGlobalError", error, { root: true });
    } finally {
        context.commit("endLoadingSwimlanes");
    }
}

export async function loadChildrenCards(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane,
): Promise<void> {
    context.commit("beginLoadingChildren", swimlane);
    try {
        const card_id = swimlane.card.id;
        await recursiveGet(`/api/v1/taskboard_cards/${card_id}/children`, {
            params: {
                milestone_id: context.rootState.milestone_id,
                limit: 100,
            },
            getCollectionCallback: (collection: Card[]): Card[] => {
                collection.forEach(injectDefaultPropertiesInCard);
                context.commit("addChildrenToSwimlane", {
                    swimlane,
                    children_cards: collection,
                });
                return collection;
            },
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    } finally {
        context.commit("endLoadingChildren", swimlane);
    }
}

export function expandSwimlane(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane,
): Promise<void> {
    context.commit("expandSwimlane", swimlane);
    const payload: UserPreference = {
        key: getPreferenceName(context, swimlane),
    };

    return context.dispatch("user/deletePreference", payload, { root: true });
}

export function collapseSwimlane(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane,
): Promise<void> {
    context.commit("collapseSwimlane", swimlane);
    const payload: UserPreferenceValue = {
        key: getPreferenceName(context, swimlane),
        value: "1",
    };

    return context.dispatch("user/setPreference", payload, { root: true });
}

function getPreferenceName(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane,
): string {
    return `plugin_taskboard_collapse_${context.rootState.milestone_id}_${swimlane.card.id}`;
}

async function getUpdatedCard(card_id: number, milestone_id: number): Promise<Card> {
    const response = await get(`/api/v1/taskboard_cards/${card_id}`, {
        params: { milestone_id },
    });
    return response.json();
}

export function refreshCardAndParent(
    context: ActionContext<SwimlaneState, RootState>,
    payload: RefreshCardActionPayload,
): Promise<void> {
    const refreshCard = (refreshed_card: Card): void =>
        context.commit("refreshCard", { refreshed_card });
    const is_solo_card = !context.getters.is_there_at_least_one_children_to_display(
        payload.swimlane,
    );
    if (is_solo_card) {
        return getUpdatedCard(payload.card.id, context.rootState.milestone_id).then(
            refreshCard,
            (error) => context.dispatch("error/handleModalError", error, { root: true }),
        );
    }

    const refresh_parent_promise = getUpdatedCard(
        payload.swimlane.card.id,
        context.rootState.milestone_id,
    ).then(refreshCard);
    const refresh_child_promise = getUpdatedCard(
        payload.card.id,
        context.rootState.milestone_id,
    ).then(refreshCard);

    return Promise.all([refresh_parent_promise, refresh_child_promise]).catch((error) =>
        context.dispatch("error/handleModalError", error, { root: true }),
    );
}
