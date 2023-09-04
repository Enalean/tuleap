import file_modifications from "./collapsible-code-sections-test-fixture";

import {
    isThereACommentOnThisLine,
    getUnchangedSections,
    getPaddedCollapsibleSections,
    getCollapsibleSectionsSideBySide,
} from "./collaspible-code-sections-builder";
import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";

describe("collapsible-code-sections-builder", () => {
    describe("isThereACommentOnThisLine", () => {
        it("Given a line and a collection of comments, Then it should return true if a comment is on the given line, false otherwise.", () => {
            const commented_line = FileLineStub.buildUnMovedFileLine(666, 666, 666);
            const not_commented_line = FileLineStub.buildUnMovedFileLine(111, 111, 111);

            const inline_comments = [
                {
                    file: {
                        unidiff_offset: 666,
                    },
                    content: "Hail to the lord of darkness!",
                } as PullRequestInlineCommentPresenter,
                {
                    file: {
                        unidiff_offset: 777,
                    },
                    content: "Wow, much luck!",
                } as PullRequestInlineCommentPresenter,
            ];

            expect(isThereACommentOnThisLine(commented_line, inline_comments)).toBe(true);

            expect(isThereACommentOnThisLine(not_commented_line, inline_comments)).toBe(false);
        });
    });

    describe("getUnchangedSections", () => {
        it("Given a file of 24 lines having a deleted line on l.12, then it should return 2 sections wrapping around the deleted line.", () => {
            const unchanged_sections = getUnchangedSections(file_modifications, []);

            expect(unchanged_sections).toStrictEqual([
                {
                    start: 0,
                    end: 10,
                },
                {
                    start: 12,
                    end: 23,
                },
            ]);
        });

        it("Given a file of 24 lines having a deleted line on l.12 and a comment on l.20, then it should return 2 section wrapping around the deleted line and the comment.", () => {
            const unchanged_sections = getUnchangedSections(file_modifications, [
                {
                    content: "A wild inline commment",
                    file: {
                        unidiff_offset: 20,
                    },
                } as PullRequestInlineCommentPresenter,
            ]);

            expect(unchanged_sections).toStrictEqual([
                {
                    start: 0,
                    end: 10,
                },
                {
                    start: 12,
                    end: 20,
                },
            ]);
        });

        it("Given a file of 24 lines, when there is a comment a the end of the file, then it shouldn't be wrapped.", () => {
            const unchanged_sections = getUnchangedSections(file_modifications, [
                {
                    content: "A wild inline commment",
                    file: {
                        unidiff_offset: 23,
                    },
                } as PullRequestInlineCommentPresenter,
            ]);

            expect(unchanged_sections).toStrictEqual([
                {
                    start: 0,
                    end: 10,
                },
                {
                    start: 12,
                    end: 22,
                },
            ]);
        });
    });

    describe("getPaddedCollapsibleSections", () => {
        it("Given potentially collapsible sections, then it should return an array containing valid padded sections.", () => {
            const file_length = 23;
            const potentially_collapsible_sections = [
                {
                    start: 0, // Can't be collapsed because end - start <= COLLAPSE THRESHOLD (10 LOCs)
                    end: 10,
                },
                {
                    start: 12,
                    end: 23,
                },
            ];

            const sections_to_collapse = getPaddedCollapsibleSections(
                potentially_collapsible_sections,
                file_length,
            );

            expect(sections_to_collapse).toStrictEqual([
                {
                    start: 22,
                    end: 23,
                },
            ]);
        });
    });

    describe("getCollapsibleSectionsSideBySide", () => {
        it("Given a file of 24 lines having a deleted line on l.12, then it should return the different sections (right/left) for each side.", () => {
            const side_by_side_collapsible_sections = getCollapsibleSectionsSideBySide(
                file_modifications,
                [],
            );

            expect(side_by_side_collapsible_sections).toStrictEqual([
                {
                    left: {
                        start: 22,
                        end: 23,
                    },
                    right: {
                        start: 21,
                        end: 22,
                    },
                },
            ]);
        });
    });
});
