import { buildEventDispatcher } from "./buildEventDispatcher";
class eventSourceMessageMock {
    retry;
    data;
    event;
    id;
    constructor(data, event, id) {
        this.retry = 1;
        this.data = data;
        this.event = event;
        this.id = id;
    }
}
describe("buildEventDispatcher -", function () {
    let callback_item_update, callback_item_moved, callback_item_create, build_event_dispatcher;
    beforeEach(function () {
        callback_item_update = jest.fn().mockImplementation();
        callback_item_create = jest.fn().mockImplementation();
        callback_item_moved = jest.fn().mockImplementation();
        build_event_dispatcher = buildEventDispatcher(
            callback_item_update,
            callback_item_moved,
            callback_item_create,
        );
    });
    function mockEventItemMove() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"kanban_item:move\\",\\"data\\":{\\"ordered_destination_column_items_ids\\":[2,1],\\"artifact_id\\":1,\\"in_column\\":101,\\"from_column\\":\\"backlog\\"}}"',
            "",
            "1",
        );
    }
    function mockEventItemUpdate() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"kanban_item:update\\",\\"data\\":{\\"artifact_id\\":1}}"',
            "",
            "1",
        );
    }

    function mockEventItemCreate() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"kanban_item:create\\",\\"data\\":{\\"artifact_id\\":4}}"',
            "",
            "",
        );
    }

    it("will get an event with kanban_item:update type and call the right function", function () {
        const message = mockEventItemUpdate();
        build_event_dispatcher(message);
        expect(callback_item_update).toHaveBeenCalled();
        expect(callback_item_moved).not.toHaveBeenCalled();
        expect(callback_item_create).not.toHaveBeenCalled();
    });
    it("will get an event with kanba_item:create type and call the right function", function () {
        const message = mockEventItemCreate();
        build_event_dispatcher(message);
        expect(callback_item_update).not.toHaveBeenCalled();
        expect(callback_item_moved).not.toHaveBeenCalled();
        expect(callback_item_create).toHaveBeenCalled();
    });
    it("will get an event with kanban_item:move type and call the right function", function () {
        const message = mockEventItemMove();
        build_event_dispatcher(message);
        expect(callback_item_update).not.toHaveBeenCalled();
        expect(callback_item_moved).toHaveBeenCalled();
        expect(callback_item_create).not.toHaveBeenCalled();
    });
});
