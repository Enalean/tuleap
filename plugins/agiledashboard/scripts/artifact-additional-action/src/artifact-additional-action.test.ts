/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { initArtifactAdditionalAction } from "./artifact-additional-action";
import * as fetch_wrapper from "@tuleap/tlp-fetch";
import * as feedbacks from "@tuleap/fp-feedback";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("Artifact additional action", () => {
    it("Does not crash when action can not be found", () => {
        expect(() => initArtifactAdditionalAction(document)).not.toThrow();
    });

    function getLocalActionWithoutProjectId(): {
        document: Document;
    } {
        const local_document = document.implementation.createHTMLDocument();
        const link_element = local_document.createElement("a");
        link_element.setAttribute("id", "artifact-explicit-backlog-action");
        local_document.body.appendChild(link_element);

        return { document: local_document };
    }

    it("Throws an error when project id can not be found", () => {
        const local_action = getLocalActionWithoutProjectId();
        expect(() => initArtifactAdditionalAction(local_action.document)).toThrow();
    });

    function getLocalActionWithoutArtifactId(): {
        document: Document;
    } {
        const local_document = document.implementation.createHTMLDocument();
        const link_element = local_document.createElement("a");
        link_element.setAttribute("id", "artifact-explicit-backlog-action");
        link_element.dataset.projectId = "101";
        local_document.body.appendChild(link_element);

        return { document: local_document };
    }

    it("Throws an error when artifact id can not be found", () => {
        const local_action = getLocalActionWithoutArtifactId();
        expect(() => initArtifactAdditionalAction(local_action.document)).toThrow();
    });

    function getLocalActionWithoutUserLocale(): {
        document: Document;
    } {
        const local_document = document.implementation.createHTMLDocument();
        const link_element = local_document.createElement("a");
        link_element.setAttribute("id", "artifact-explicit-backlog-action");
        link_element.dataset.projectId = "101";
        link_element.dataset.artifactId = "201";
        local_document.body.appendChild(link_element);

        return { document: local_document };
    }

    it("Throws an error when user locale can not be found", () => {
        const local_action = getLocalActionWithoutUserLocale();
        expect(() => initArtifactAdditionalAction(local_action.document)).toThrow();
    });

    it("Throws an error when the title of the button can not be found", () => {
        const local_action = getLocalActionWithoutUserLocale();
        local_action.document.body.dataset.userLocale = "en_US";
        expect(() => initArtifactAdditionalAction(local_action.document)).toThrow();
    });

    interface LocalAction {
        document: Document;
        button_wrapper: HTMLElement;
        link_element: HTMLAnchorElement;
        title_element: HTMLAnchorElement;
        icon: HTMLElement;
    }

    function getLocalAddAction(): LocalAction {
        const local_document = document.implementation.createHTMLDocument();
        const link_element = local_document.createElement("a");
        link_element.setAttribute("id", "artifact-explicit-backlog-action");
        link_element.dataset.projectId = "101";
        link_element.dataset.artifactId = "201";
        link_element.dataset.action = "add";

        const icon = local_document.createElement("i");
        link_element.appendChild(icon);

        const span_element = local_document.createElement("span");
        span_element.setAttribute("class", "additional-artifact-action-title");
        link_element.appendChild(span_element);
        local_document.body.dataset.userLocale = "en_US";
        const button_wrapper = local_document.createElement("div");
        button_wrapper.appendChild(link_element);
        local_document.body.appendChild(button_wrapper);

        return {
            document: local_document,
            button_wrapper,
            link_element: link_element,
            title_element: link_element,
            icon,
        };
    }

    function getLocalRemoveAction(): LocalAction {
        const local_action = getLocalAddAction();
        local_action.link_element.dataset.action = "remove";

        return local_action;
    }

    it("Adds the artifact into top backlog", () => {
        return new Promise<void>((done) => {
            const local_action = getLocalAddAction();
            const spyPatch = jest.spyOn(fetch_wrapper, "patch");
            mockFetchSuccess(spyPatch, {});
            const spyClearFeedbacks = jest.spyOn(feedbacks, "clearAllFeedbacks");
            const spyAddFeedback = jest.spyOn(feedbacks, "addFeedback");

            initArtifactAdditionalAction(local_action.document);

            const current_button_title = local_action.title_element.textContent;

            local_action.link_element.click();

            setTimeout(() => {
                expect(spyClearFeedbacks).toHaveBeenCalled();
                expect(spyAddFeedback).toHaveBeenCalledWith("info", expect.anything());
                expect(spyPatch).toHaveBeenCalledWith(expect.anything(), {
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        add: [{ id: 201 }],
                    }),
                });
                expect(current_button_title).not.toBe(local_action.title_element.textContent);
                expect(local_action.icon.classList.contains("fa-tlp-add-to-backlog")).toBe(false);
                expect(local_action.icon.classList.contains("fa-tlp-remove-from-backlog")).toBe(
                    true,
                );
                done();
            });
        });
    });

    it("Removes the artifact from the top backlog", () => {
        return new Promise<void>((done) => {
            const local_action = getLocalRemoveAction();
            const spyPatch = jest.spyOn(fetch_wrapper, "patch");
            mockFetchSuccess(spyPatch, {});
            const spyClearFeedbacks = jest.spyOn(feedbacks, "clearAllFeedbacks");
            const spyAddFeedback = jest.spyOn(feedbacks, "addFeedback");

            initArtifactAdditionalAction(local_action.document);

            const current_button_title = local_action.title_element.textContent;

            local_action.link_element.click();

            setTimeout(() => {
                expect(spyClearFeedbacks).toHaveBeenCalled();
                expect(spyAddFeedback).toHaveBeenCalledWith("info", expect.anything());
                expect(spyPatch).toHaveBeenCalledWith(expect.anything(), {
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        remove: [{ id: 201 }],
                    }),
                });
                expect(current_button_title).not.toBe(local_action.title_element.textContent);
                expect(local_action.icon.classList.contains("fa-tlp-add-to-backlog")).toBe(true);
                expect(local_action.icon.classList.contains("fa-tlp-remove-from-backlog")).toBe(
                    false,
                );
                done();
            });
        });
    });

    it("Does nothing when the action is disabled", () => {
        return new Promise<void>((done) => {
            const local_action = getLocalAddAction();
            local_action.button_wrapper.classList.add("disabled");
            const spyPatch = jest.spyOn(fetch_wrapper, "patch");
            const spyClearFeedbacks = jest.spyOn(feedbacks, "clearAllFeedbacks");
            const spyAddFeedback = jest.spyOn(feedbacks, "addFeedback");

            initArtifactAdditionalAction(local_action.document);

            local_action.link_element.click();

            setTimeout(() => {
                expect(spyClearFeedbacks).not.toHaveBeenCalled();
                expect(spyAddFeedback).not.toHaveBeenCalled();
                expect(spyPatch).not.toHaveBeenCalled();
                done();
            });
        });
    });

    it("Deals with error when trying to add the artifact into the top backlog", () => {
        return new Promise<void>((done) => {
            expect(() => testActionErrorManagement(getLocalAddAction(), done)).not.toThrow();
        });
    });

    it("Deals with error when trying to remove the artifact from the top backlog", () => {
        return new Promise<void>((done) => {
            expect(() => testActionErrorManagement(getLocalRemoveAction(), done)).not.toThrow();
        });
    });

    function testActionErrorManagement(local_action: LocalAction, done: () => void): void {
        const spyPatch = jest.spyOn(fetch_wrapper, "patch");
        mockFetchError(spyPatch, {});
        const spyClearFeedbacks = jest.spyOn(feedbacks, "clearAllFeedbacks");
        const spyAddFeedback = jest.spyOn(feedbacks, "addFeedback");

        initArtifactAdditionalAction(local_action.document);

        const current_button_title = local_action.title_element.textContent;

        local_action.link_element.click();

        setTimeout(() => {
            expect(spyClearFeedbacks).toHaveBeenCalled();
            expect(spyAddFeedback).toHaveBeenCalledWith("error", expect.anything());
            expect(current_button_title).toBe(local_action.title_element.textContent);
            done();
        });
    }
});
