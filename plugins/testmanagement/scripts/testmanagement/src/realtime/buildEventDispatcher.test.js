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
    let callback_execution_updated,
        callback_execution_deleted,
        callback_execution_created,
        callback_artifact_linked,
        callback_campaign_updated,
        build_event_dispatcher;
    beforeEach(function () {
        callback_artifact_linked = jest.fn().mockImplementation();
        callback_execution_updated = jest.fn().mockImplementation();
        callback_execution_deleted = jest.fn().mockImplementation();
        callback_execution_created = jest.fn().mockImplementation();
        callback_campaign_updated = jest.fn().mockImplementation();
        build_event_dispatcher = buildEventDispatcher(
            callback_execution_created,
            callback_execution_updated,
            callback_execution_deleted,
            callback_artifact_linked,
            callback_campaign_updated,
        );
    });
    function mockExecutionUpdated() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"testmanagement_execution:update\\",\\"data\\":{}}"',
            "",
            1,
        );
    }
    function mockExecutionCreated() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"testmanagement_execution:create\\",\\"data\\":{}}"',
            "",
            1,
        );
    }
    function mockExecutionDeleted() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"testmanagement_execution:delete\\",\\"data\\":{}}"',
            "",
            1,
        );
    }
    function mockArtifactLinked() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"testmanagement_execution:link_artifact\\",\\"data\\":{}}"',
            "",
            1,
        );
    }
    function mockCampaignUpdated() {
        return new eventSourceMessageMock(
            '"{\\"cmd\\":\\"testmanagement_campaign:update\\",\\"data\\":{}}"',
            "",
            1,
        );
    }

    it("will get an event with testmanagement_execution:update and call the right function", function () {
        const message = mockExecutionUpdated();
        build_event_dispatcher(message);
        expect(callback_execution_updated).toHaveBeenCalled();
        expect(callback_execution_created).not.toHaveBeenCalled();
        expect(callback_execution_deleted).not.toHaveBeenCalled();
        expect(callback_artifact_linked).not.toHaveBeenCalled();
        expect(callback_campaign_updated).not.toHaveBeenCalled();
    });
    it("will get an event with testmanagement_execution:create and call the right function", function () {
        const message = mockExecutionCreated();
        build_event_dispatcher(message);
        expect(callback_execution_updated).not.toHaveBeenCalled();
        expect(callback_execution_created).toHaveBeenCalled();
        expect(callback_execution_deleted).not.toHaveBeenCalled();
        expect(callback_artifact_linked).not.toHaveBeenCalled();
        expect(callback_campaign_updated).not.toHaveBeenCalled();
    });
    it("will get an event with testmanagement_execution:delete and call the right function", function () {
        const message = mockExecutionDeleted();
        build_event_dispatcher(message);
        expect(callback_execution_updated).not.toHaveBeenCalled();
        expect(callback_execution_created).not.toHaveBeenCalled();
        expect(callback_execution_deleted).toHaveBeenCalled();
        expect(callback_artifact_linked).not.toHaveBeenCalled();
        expect(callback_campaign_updated).not.toHaveBeenCalled();
    });
    it("will get an event with testmanagement_execution:artifact_linked and call the right function", function () {
        const message = mockArtifactLinked();
        build_event_dispatcher(message);
        expect(callback_execution_updated).not.toHaveBeenCalled();
        expect(callback_execution_created).not.toHaveBeenCalled();
        expect(callback_execution_deleted).not.toHaveBeenCalled();
        expect(callback_artifact_linked).toHaveBeenCalled();
        expect(callback_campaign_updated).not.toHaveBeenCalled();
    });
    it("will get an event with testmanagement_campaign:update and call the right function", function () {
        const message = mockCampaignUpdated();
        build_event_dispatcher(message);
        expect(callback_execution_updated).not.toHaveBeenCalled();
        expect(callback_execution_created).not.toHaveBeenCalled();
        expect(callback_execution_deleted).not.toHaveBeenCalled();
        expect(callback_artifact_linked).not.toHaveBeenCalled();
        expect(callback_campaign_updated).toHaveBeenCalled();
    });
});
