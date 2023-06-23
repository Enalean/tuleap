/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import { selectOrThrow } from "@tuleap/dom";
import { CommentUserPreferencesBuilder } from "../../../../tests/builders/CommentUserPreferencesBuilder";
import type { HostElement } from "./ModalCommentsSection";
import {
    getNewCommentClasses,
    getSectionClasses,
    getSectionTemplate,
    onValueChanged,
} from "./ModalCommentsSection";
import type { NewComment } from "../../../domain/comments/NewComment";
import { CommentsPresenter } from "./CommentsPresenter";
import { setCatalog } from "../../../gettext-catalog";
import type { FollowupEditor } from "./FollowupEditor";
import { CommentsController } from "../../../domain/comments/CommentsController";
import { RetrieveCommentsStub } from "../../../../tests/stubs/RetrieveCommentsStub";
import { CurrentArtifactIdentifierStub } from "../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { CommentUserPreferences } from "../../../domain/comments/CommentUserPreferences";
import { DispatchEventsStub } from "../../../../tests/stubs/DispatchEventsStub";

describe(`ModalCommentsSection`, () => {
    describe(`events`, () => {
        let dispatchEvent: jest.SpyInstance, host: HostElement;

        beforeEach(() => {
            dispatchEvent = jest.fn();
            host = {
                dispatchEvent,
            } as unknown as HostElement;
        });

        it(`dispatches a "new-comment" event when it receives a "value-changed" event from the follow-up editor`, () => {
            const body = "A *CommonMark* comment";
            const format = TEXT_FORMAT_COMMONMARK;
            onValueChanged(
                host,
                new CustomEvent<NewComment>("value-changed", {
                    detail: { body, format },
                })
            );
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("new-comment");
            expect(event.detail.body).toBe(body);
            expect(event.detail.format).toBe(format);
        });
    });

    it.each([[true], [false]])(
        `sets "invert-order" class to %s on new comment form`,
        (is_order_inverted) => {
            const classes = getNewCommentClasses(is_order_inverted);
            expect(classes["invert-order"]).toBe(is_order_inverted);
        }
    );

    it.each([[true], [false]])(
        `sets "invert-order" class to %s on comments section`,
        (is_order_inverted) => {
            const classes = getSectionClasses(is_order_inverted);
            expect(classes["invert-order"]).toBe(is_order_inverted);
        }
    );

    describe(`template`, () => {
        let target: ShadowRoot, presenter: CommentsPresenter, preferences: CommentUserPreferences;

        beforeEach(() => {
            setCatalog({ getString: (msgid) => msgid });
            const doc = document.implementation.createHTMLDocument();
            target = doc.createElement("div") as unknown as ShadowRoot;
            preferences = CommentUserPreferencesBuilder.userPreferences().build();
            presenter = CommentsPresenter.fromCommentsAndPreferences([], preferences);
        });

        const render = (): void => {
            const host = {
                presenter,
                controller: CommentsController(
                    RetrieveCommentsStub.withoutComments(),
                    DispatchEventsStub.buildNoOp(),
                    CurrentArtifactIdentifierStub.withId(91),
                    preferences
                ),
            } as HostElement;
            const update = getSectionTemplate(host);
            return update(host, target);
        };

        it(`renders an empty state when there are not comments`, () => {
            render();

            expect(target.querySelector("[data-test=comments-empty]")).not.toBeNull();
        });

        it(`renders a spinner when comments are loading`, () => {
            presenter = CommentsPresenter.buildLoading(preferences);

            render();

            expect(target.querySelector("[data-test=comments-spinner]")).not.toBeNull();
        });

        it(`does not render a follow-up editor when user is not allowed to add a new comment`, () => {
            preferences = CommentUserPreferencesBuilder.userPreferences()
                .withNewCommentAllowed(false)
                .build();
            presenter = CommentsPresenter.fromCommentsAndPreferences([], preferences);

            render();

            expect(target.querySelector("[data-test=add-comment-form]")).toBeNull();
        });

        it(`renders a follow-up editor when user is allowed to add a comment`, () => {
            preferences = CommentUserPreferencesBuilder.userPreferences()
                .withDefaultTextFormat(TEXT_FORMAT_HTML)
                .withNewCommentAllowed(true)
                .build();
            presenter = CommentsPresenter.fromCommentsAndPreferences([], preferences);

            render();

            const followup_editor = selectOrThrow(
                target,
                "[data-test=add-comment-form]"
            ) as FollowupEditor & HTMLElement;
            expect(followup_editor.format).toBe(TEXT_FORMAT_HTML);
        });
    });
});
