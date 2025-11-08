/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { uri, getJSON } from "@tuleap/fetch-result";
import type { UserGroup, User } from "../type";

type SubscriptionType = "plugin_docman" | "plugin_docman_cascade" | "from_parent";

export const TO_ITEM: SubscriptionType = "plugin_docman";
export const TO_ITEM_AND_SUBHIERARCHY: SubscriptionType = "plugin_docman_cascade";
export const FROM_PARENT: SubscriptionType = "from_parent";

export type SubscriberList = {
    users: UserSubscription[];
    ugroups: UGroupSubscription[];
};

type UserSubscription = {
    subscriber: User;
    subscription_type: SubscriptionType;
};

type UGroupSubscription = {
    subscriber: UserGroup;
    subscription_type: SubscriptionType;
};

export function getSubscribers(item_id: number): ResultAsync<SubscriberList, Fault> {
    return getJSON<SubscriberList>(uri`/plugins/document/${item_id}/subscribers`);
}
