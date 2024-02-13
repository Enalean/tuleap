/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import type * as fetch from "@microsoft/fetch-event-source";

export function buildEventDispatcher(
    callback_execution_created: (event: fetch.EventSourceMessage) => void,
    callback_execution_updated: (event: fetch.EventSourceMessage) => void,
    callback_execution_deleted: (event: fetch.EventSourceMessage) => void,
    callback_artifact_linked: (event: fetch.EventSourceMessage) => void,
    callback_campaign_updated: (event: fetch.EventSourceMessage) => void,
    callback_presence_updated: (event: fetch.EventSourceMessage) => void,
): (event: fetch.EventSourceMessage) => void {
    return (event: fetch.EventSourceMessage): void => {
        const data_message = JSON.parse(JSON.parse(event.data));
        if (data_message.cmd === "testmanagement_execution:create") {
            callback_execution_created(data_message);
        } else if (data_message.cmd === "testmanagement_execution:delete") {
            callback_execution_deleted(data_message);
        } else if (data_message.cmd === "testmanagement_execution:update") {
            callback_execution_updated(data_message);
        } else if (data_message.cmd === "testmanagement_execution:link_artifact") {
            callback_artifact_linked(data_message);
        } else if (data_message.cmd === "testmanagement_campaign:update") {
            callback_campaign_updated(data_message);
        } else if (data_message.cmd === "testmanagement_user:presence") {
            callback_presence_updated(data_message);
        }
    };
}
