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

import { describe, it, expect, beforeEach, vi } from "vitest";
import * as TUS from "tus-js-client";
import { mockFetchSuccess, mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "./file-upload-handler-factory.js";
import * as tlp_fetch from "@tuleap/tlp-fetch";

vi.mock("@tuleap/tlp-fetch");
vi.mock("tus-js-client");

describe(`file-upload-handler-factory`, () => {
    let file_upload_event,
        loader,
        options = {};
    beforeEach(() => {
        options = {
            ckeditor_instance: {
                fire: vi.fn(),
            },
            onStartCallback: vi.fn(),
            onErrorCallback: vi.fn(),
            onSuccessCallback: vi.fn(),
            max_size_upload: 100,
        };
        loader = {
            changeStatus: vi.fn(),
            update: vi.fn(),
            file: {
                name: "pentacyanic.jpg",
                type: "image/jpg",
            },
            lang: {
                filetools: [],
            },
        };
        file_upload_event = {
            data: {
                fileLoader: loader,
            },
            stop: vi.fn(),
        };
    });

    function handlerFactory() {
        return buildFileUploadHandler(options);
    }

    describe(`buildFileUploadHandler() - when a fileUploadRequest event is dispatched`, () => {
        describe(`component is initialized`, () => {
            beforeEach(async () => {
                mockFetchSuccess(vi.spyOn(tlp_fetch, "post"), {
                    return_json: {
                        id: 71,
                        download_href: "https://example.com/extenuator",
                    },
                });

                const handler = handlerFactory();
                await handler(file_upload_event);
            });

            it(`will stop the event`, () => expect(file_upload_event.stop).toHaveBeenCalled());
            it(`will call the onStartCallback`, () =>
                expect(options.onStartCallback).toHaveBeenCalled());
        });

        describe(`when the file size is above the max upload size`, () => {
            beforeEach(async () => {
                options.max_size_upload = 100;
                loader.file.size = 1024;

                const handler = handlerFactory();
                await handler(file_upload_event);
            });

            it(`will call the Error callback with a MaxSizeUploadExceededError`, () => {
                expect(options.onErrorCallback).toHaveBeenCalled();
                const [error] = options.onErrorCallback.mock.calls[0];

                expect(error instanceof MaxSizeUploadExceededError).toBe(
                    true,
                    "Expected error to be a MaxSizeUploadExceededError",
                );
                expect(error.loader).toBe(loader, "Expected error.loader to be the file loader");
                expect(error.max_size_upload).toEqual(
                    100,
                    "Expected error.max_size_upload to equal 100",
                );
            });

            it(`then the loader will show an error`, () =>
                expect(loader.changeStatus).toHaveBeenCalledWith("error"));
        });

        describe(`when the file size is ok`, () => {
            beforeEach(() => {
                options.max_size_upload = 1024;
                loader.file = {
                    size: 1024,
                };
            });

            it(`will post to the loader's uploadUrl`, async () => {
                loader.fileName = "pulpitism.png";
                loader.uploadUrl = "https://example.com/upload_url";
                loader.file.type = "image/png";
                const tlpPost = vi.spyOn(tlp_fetch, "post");
                mockFetchSuccess(tlpPost, {
                    return_json: {
                        id: 25,
                        download_url: "https://example.com/download_url",
                    },
                });

                const handler = handlerFactory();
                await handler(file_upload_event);

                expect(tlpPost).toHaveBeenCalledWith("https://example.com/upload_url", {
                    headers: { "content-type": "application/json" },
                    body: JSON.stringify({
                        name: "pulpitism.png",
                        file_size: 1024,
                        file_type: "image/png",
                    }),
                });
            });

            describe(`when the POST endpoint returns an error`, () => {
                it(`will call the Error callback with an UploadError`, async () => {
                    vi.spyOn(tlp_fetch, "post").mockReturnValue(Promise.reject({}));

                    const handler = handlerFactory();
                    await expect(handler(file_upload_event)).rejects.toBeDefined();
                    expect(options.onErrorCallback).toHaveBeenCalled();
                    const [error] = options.onErrorCallback.mock.calls[0];

                    expect(error instanceof UploadError).toBe(
                        true,
                        "Expected error to be an UploadError",
                    );
                    expect(error.loader).toBe(
                        loader,
                        "Expected error.loader to be the file loader",
                    );
                    expect(loader.changeStatus).toHaveBeenCalledWith("error");
                });

                it(`when the error is translated, then it will show the translated error`, async () => {
                    mockFetchError(vi.spyOn(tlp_fetch, "post"), {
                        error_json: {
                            error: {
                                i18n_error_message: "Problème durant le téléversement",
                            },
                        },
                    });

                    const handler = handlerFactory();
                    await expect(handler(file_upload_event)).rejects.toBeDefined();
                    expect(loader.message).toBe("Problème durant le téléversement");
                    expect(loader.changeStatus).toHaveBeenCalledWith("error");
                });

                it(`when the error is not translated, then it will show its message`, async () => {
                    mockFetchError(vi.spyOn(tlp_fetch, "post"), {
                        error_json: {
                            error: {
                                message: "Untranslated error message",
                            },
                        },
                    });

                    const handler = handlerFactory();
                    await expect(handler(file_upload_event)).rejects.toBeDefined();
                    expect(loader.message).toBe("Untranslated error message");
                    expect(loader.changeStatus).toHaveBeenCalledWith("error");
                });
            });

            describe(`when the POST endpoint does not return an upload_href`, () => {
                beforeEach(() => {
                    mockFetchSuccess(vi.spyOn(tlp_fetch, "post"), {
                        return_json: {
                            id: 71,
                            download_href: "https://example.com/download_url",
                        },
                    });
                });

                it(`will mark the upload as successful in the loader`, async () => {
                    loader.file.name = "pentacyanic.jpg";

                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(loader.uploaded).toBe(1);
                    expect(loader.fileName).toBe("pentacyanic.jpg");
                    expect(loader.url).toBe("https://example.com/download_url");
                    expect(loader.changeStatus).toHaveBeenCalledWith("uploaded");
                });

                it(`will stop CKEditor from breaking artifact display with width & height`, async () => {
                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(loader.responseData).toEqual({
                        width: " ",
                        height: " ",
                    });
                });

                it(`will call the Success callback with the returned id and download_href`, async () => {
                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(options.onSuccessCallback).toHaveBeenCalledWith(
                        71,
                        "https://example.com/download_url",
                    );
                });
            });

            describe(`when the POST endpoint returns an upload_href`, () => {
                beforeEach(() => {
                    mockFetchSuccess(vi.spyOn(tlp_fetch, "post"), {
                        return_json: {
                            id: 23,
                            download_href: "https://example.com/download_url",
                            upload_href: "https://example.com/postloitic",
                        },
                    });
                });

                it(`will call the Start callback`, async () => {
                    TUS.Upload.mockImplementation((file, config) => ({
                        start: () => config.onSuccess(),
                    }));

                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(options.onStartCallback).toHaveBeenCalled();
                });

                describe(`will create and start the TUS uploader`, () => {
                    it(`and when the uploader receives the onProgress event,
                            it will update the loader with the bytes send and total`, async () => {
                        TUS.Upload.mockImplementation((file, config) => ({
                            start: () => {
                                config.onProgress(256, 1024);
                                expect(loader.uploadTotal).toBe(1024);
                                expect(loader.uploaded).toBe(256);
                                expect(loader.update).toHaveBeenCalled();

                                config.onSuccess();
                            },
                        }));
                        const handler = handlerFactory();
                        await handler(file_upload_event);
                    });

                    describe(`and when the uploader receives the onSuccess event`, () => {
                        beforeEach(() => {
                            TUS.Upload.mockImplementation((file, config) => ({
                                start: () => config.onSuccess(),
                            }));
                        });

                        it(`will mark the upload as successful in the loader`, async () => {
                            loader.file.name = "pentacyanic.jpg";

                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(loader.uploaded).toBe(1);
                            expect(loader.fileName).toBe("pentacyanic.jpg");
                            expect(loader.url).toBe("https://example.com/download_url");
                            expect(loader.changeStatus).toHaveBeenCalledWith("uploaded");
                        });

                        it(`will stop CKEditor from breaking artifact display with width & height`, async () => {
                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(loader.responseData).toEqual({
                                width: " ",
                                height: " ",
                            });
                        });

                        it(`will dispatch the "change" event on the CKEditor instance`, async () => {
                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(options.ckeditor_instance.fire).toHaveBeenCalledWith("change");
                        });

                        it(`will call the Success callback with the returned id and download_href`, async () => {
                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(options.onSuccessCallback).toHaveBeenCalledWith(
                                23,
                                "https://example.com/download_url",
                            );
                        });
                    });

                    describe(`and when the uploader receives the onError event`, () => {
                        let error;
                        beforeEach(() => {
                            TUS.Upload.mockImplementation((file, config) => ({
                                start: () => config.onError(error),
                            }));
                        });

                        it(`when CKEditor has a dedicated httpError message,
                                then it will show it`, async () => {
                            error = { originalRequest: { status: 403 } };
                            loader.lang = {
                                filetools: {
                                    httpError403: "Translated error message",
                                },
                            };

                            const handler = handlerFactory();
                            await expect(handler(file_upload_event)).rejects.toBe(error);
                            expect(loader.message).toBe("Translated error message");
                            expect(options.onErrorCallback).toHaveBeenCalledWith(
                                new UploadError(loader),
                            );
                        });

                        it(`when CKEditor has no dedicated httpError message,
                                then it will show a generic message from CKEditor`, async () => {
                            error = { originalRequest: { status: 500 } };
                            loader.lang = {
                                filetools: {
                                    httpError: "Error %1",
                                },
                            };

                            const handler = handlerFactory();
                            await expect(handler(file_upload_event)).rejects.toBe(error);
                            expect(loader.message).toBe("Error 500");
                            expect(options.onErrorCallback).toHaveBeenCalledWith(
                                new UploadError(loader),
                            );
                        });

                        it(`will mark the upload as failed in the loader`, async () => {
                            error = { originalRequest: { status: 500 } };
                            loader.lang = {
                                filetools: {
                                    httpError: "",
                                },
                            };
                            const handler = handlerFactory();
                            await expect(handler(file_upload_event)).rejects.toBe(error);
                            expect(loader.changeStatus).toHaveBeenCalledWith("error");
                            expect(options.onErrorCallback).toHaveBeenCalledWith(
                                new UploadError(loader),
                            );
                        });
                    });
                });
            });
        });
    });
});
