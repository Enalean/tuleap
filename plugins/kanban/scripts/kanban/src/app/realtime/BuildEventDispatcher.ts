import type { EventSourceMessage } from "@microsoft/fetch-event-source";

export function BuildEventDispatcher(
    callback_item_update: (event: EventSourceMessage) => void,
    callback_item_move: (event: EventSourceMessage) => void,
    callback_item_create: (event: EventSourceMessage) => void,
    callback_structural_update: () => void,
): (event: EventSourceMessage) => void {
    return (event: EventSourceMessage): void => {
        if (event.data === '"kanban_structure_update"') {
            callback_structural_update();
        } else {
            const data_message = JSON.parse(JSON.parse(event.data));
            if (data_message.cmd === "kanban_item:update") {
                callback_item_update(data_message);
            } else if (data_message.cmd === "kanban_item:move") {
                callback_item_move(data_message);
            } else if (data_message.cmd === "kanban_item:create") {
                callback_item_create(data_message);
            }
        }
    };
}
