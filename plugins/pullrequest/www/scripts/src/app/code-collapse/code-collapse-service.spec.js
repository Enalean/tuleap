import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";
import file_modifications from "./code-collapse-service.fixtures.spec.js";

import "angular-mocks";

describe("code-collapse-service", () => {
    let CodeCollapseService;

    beforeEach(function() {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function(_CodeCollapseService_) {
            CodeCollapseService = _CodeCollapseService_;
        });
    });

    describe("isThereACommentOnThisLine", () => {
        it("Given a line number and a collection of comments, Then it should return true if a comment is on the given line, false otherwise.", () => {
            const commented_line_number = 666;
            const not_commented_line_number = 111;

            const inline_comments = [
                {
                    unidiff_offset: 666,
                    comment: "Hail to the lord of darkness!"
                },
                {
                    unidiff_offset: 777,
                    comment: "Wow, much luck!"
                }
            ];

            expect(
                CodeCollapseService.isThereACommentOnThisLine(
                    commented_line_number,
                    inline_comments
                )
            ).toBe(true);

            expect(
                CodeCollapseService.isThereACommentOnThisLine(
                    not_commented_line_number,
                    inline_comments
                )
            ).toBe(false);
        });
    });

    describe("getCollapsibleSections", () => {
        it("Given a file of 24 lines having a deleted line on l.12, then it should return 2 sections wrapping around the deleted line.", () => {
            const unchanged_sections = CodeCollapseService.getUnchangedSections(
                file_modifications,
                []
            );

            expect(unchanged_sections).toEqual([
                {
                    start: 0,
                    end: 10
                },
                {
                    start: 12,
                    end: 23
                }
            ]);
        });

        it("Given a file of 24 lines having a deleted line on l.12 and a comment on l.20, then it should return 2 section wrapping around the deleted line and the comment.", () => {
            const unchanged_sections = CodeCollapseService.getUnchangedSections(
                file_modifications,
                [{ unidiff_offset: 20 }]
            );

            expect(unchanged_sections).toEqual([
                {
                    start: 0,
                    end: 10
                },
                {
                    start: 12,
                    end: 20
                }
            ]);
        });
    });

    describe("getPaddedCollapsibleSections", () => {
        it("Given potentially collapsible sections, then it should return an array containing valid padded sections.", () => {
            const file_length = 23;
            const potentially_collapsible_sections = [
                {
                    start: 0, // Can't be collapsed because end - start <= COLLAPSE THRESHOLD (10 LOCs)
                    end: 10
                },
                {
                    start: 12,
                    end: 23
                }
            ];

            const sections_to_collapse = CodeCollapseService.getPaddedCollapsibleSections(
                potentially_collapsible_sections,
                file_length
            );

            expect(sections_to_collapse).toEqual([
                {
                    start: 22,
                    end: 23
                }
            ]);
        });
    });
});
