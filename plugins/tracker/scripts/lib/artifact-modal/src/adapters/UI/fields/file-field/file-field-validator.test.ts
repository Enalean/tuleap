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

import { validateFileField } from "./file-field-validator";
import type { FileValueModel, FollowupValueModel } from "./file-field-validator";
import type { TextFieldValueModel } from "../text-field/text-field-value-formatter";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";

const getFileValueModel = (data: Partial<FileValueModel>): FileValueModel => {
    return {
        field_id: 6476,
        type: "file",
        label: "Attachments",
        file_descriptions: [
            {
                id: 429,
                submitted_by: 110,
                description: "favicon",
                name: "favicon.ico",
                size: 1022,
                type: "image/vnd.microsoft.icon",
                html_url: "/plugins/tracker/attachments/429-favicon.ico",
                html_preview_url: null,
                uri: "artifact_files/429",
                display_as_image: true,
            },
            {
                id: 481,
                submitted_by: 110,
                description: "",
                name: "pullrequest-pr-diff-comment.png",
                size: 20866,
                type: "image/png",
                html_url: "/plugins/tracker/attachments/481-pullrequest-pr-diff-comment.png",
                html_preview_url:
                    "/plugins/tracker/attachments/preview/481-pullrequest-pr-diff-comment.png",
                uri: "artifact_files/481",
                display_as_image: true,
            },
        ],
        images_added_by_text_fields: [],
        permissions: ["read", "update", "create"],
        temporary_files: [{ file: {}, description: "" }],
        value: [],
        ...data,
    } as FileValueModel;
};

describe(`file-field-validator`, () => {
    describe(`validateFileField()
        Given a file value model,
        a list of text field value models
        and the new followup value model`, () => {
        let text_field_value_models: ReadonlyArray<TextFieldValueModel>,
            followup_value_model: FollowupValueModel;
        beforeEach(() => {
            text_field_value_models = [];
            followup_value_model = {
                body: "",
                format: "text",
            };
        });

        it(`when the value model is undefined, it will return null`, () => {
            const result = validateFileField(
                undefined,
                text_field_value_models,
                followup_value_model,
            );

            expect(result).toBeNull();
        });

        it(`when the file's value is empty, it will return "field_id" and empty "value"`, () => {
            const result = validateFileField(
                getFileValueModel({ value: [] }),
                text_field_value_models,
                followup_value_model,
            );

            expect(result).toStrictEqual({
                field_id: 6476,
                value: [],
            });
        });

        it(`will return a value model with only "field_id" and "value" attributes`, () => {
            const result = validateFileField(
                getFileValueModel({ value: [429, 481] }),
                text_field_value_models,
                followup_value_model,
            );

            expect(result).toStrictEqual({
                field_id: 6476,
                value: [429, 481],
            });
        });

        describe(`and files have been added directly on the file field`, () => {
            it(`will keep the files' ids`, () => {
                const result = validateFileField(
                    getFileValueModel({ value: [429, 481] }),
                    text_field_value_models,
                    followup_value_model,
                );

                expect(result?.value).toContain(429);
                expect(result?.value).toContain(481);
            });
        });

        describe(`and files have been added by Text field image upload`, () => {
            let file_value_model: FileValueModel,
                text_field_referencing_an_image: TextFieldValueModel,
                other_text_field: TextFieldValueModel;
            beforeEach(() => {
                file_value_model = getFileValueModel({
                    images_added_by_text_fields: [
                        { id: 127, download_href: "https://example.com/answerably.jpg" },
                        { id: 142, download_href: "https://example.com/carboxylation.gif" },
                    ],
                    value: [429, 481, 127, 142],
                });

                text_field_referencing_an_image = {
                    field_id: 6401,
                    value: {
                        content: `<p><img src="https://example.com/answerably.jpg"></p>`,
                        format: TEXT_FORMAT_HTML,
                    },
                };
                other_text_field = {
                    field_id: 6959,
                    value: {
                        content: `![](<p><img src="https://example.com/carboxylation.gif"></p>)`,
                        format: TEXT_FORMAT_COMMONMARK,
                    },
                };
                text_field_value_models = [text_field_referencing_an_image, other_text_field];
            });

            describe(`and those image urls were still referenced
                    in text field value models`, () => {
                it(`will keep the image files' ids`, () => {
                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).toContain(127);
                    expect(result?.value).toContain(142);
                });
            });

            describe(`and those image urls were still referenced
                in followup comment value model`, () => {
                beforeEach(() => {
                    text_field_value_models = [];
                    followup_value_model = {
                        body: `<p>
                            <img src="https://example.com/answerably.jpg">
                            <img src="https://example.com/carboxylation.gif">
                        </p>`,
                        format: TEXT_FORMAT_HTML,
                    };
                });

                it(`will keep the image files' ids`, () => {
                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).toContain(127);
                    expect(result?.value).toContain(142);
                });

                it(`and the followup format has been switched to text format,
                    then it will filter out the image files' ids`, () => {
                    followup_value_model = {
                        ...followup_value_model,
                        format: TEXT_FORMAT_TEXT,
                    };

                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).not.toContain(127);
                    expect(result?.value).not.toContain(142);
                });
            });

            describe(`and those image urls were removed from the text
                    fields`, () => {
                it(`will filter out the image files' ids`, () => {
                    text_field_value_models = [
                        text_field_referencing_an_image,
                        {
                            ...other_text_field,
                            value: {
                                ...other_text_field.value,
                                content: `<p>Some text</p>`,
                            },
                        },
                    ];

                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).not.toContain(142);
                });
            });

            describe(`and one of the text field has been switched to text format`, () => {
                it(`will filter out the image files' ids`, () => {
                    text_field_value_models = [
                        text_field_referencing_an_image,
                        {
                            ...other_text_field,
                            value: {
                                ...other_text_field.value,
                                format: TEXT_FORMAT_TEXT,
                            },
                        },
                    ];

                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).not.toContain(142);
                });
            });

            describe(`and files have also been added directly on the file field`, () => {
                it(`will keep the files' ids`, () => {
                    const result = validateFileField(
                        file_value_model,
                        text_field_value_models,
                        followup_value_model,
                    );

                    expect(result?.value).toContain(429);
                    expect(result?.value).toContain(481);
                });
            });
        });
    });
});
