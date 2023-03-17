import type * as fetch from "@microsoft/fetch-event-source";
export function buildEventDispatcher(
    callback_item_update: (event: fetch.EventSourceMessage) => void,
    callback_item_move: (event: fetch.EventSourceMessage) => void,
    callback_item_create: (event: fetch.EventSourceMessage) => void
): (event: fetch.EventSourceMessage) => void {
    return (event: fetch.EventSourceMessage): void => {
        const data_message = JSON.parse(JSON.parse(event.data));

        if (data_message.cmd === "kanban_item:update") {
            callback_item_update(data_message);
        } else if (data_message.cmd === "kanban_item:move") {
            callback_item_move(data_message);
        } else if (data_message.cmd === "kanban_item:create") {
            callback_item_create(data_message);
        }
    };
}
