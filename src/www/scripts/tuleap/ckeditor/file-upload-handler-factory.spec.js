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

import { rewire$post, restore as restoreTLP } from "tlp-fetch";
import * as TUS from "tus-js-client";
import { mockFetchSuccess, mockFetchError } from "tlp-mocks";
import { rewire$getGettextProvider, restore as restoreGettext } from "./gettext-factory.js";
import { buildFileUploadHandler } from "./file-upload-handler-factory.js";

describe(`file-upload-handler-factory`, () => {
    let file_upload_event,
        loader,
        options = {},
        getGettextProvider,
        post;
    beforeEach(() => {
        getGettextProvider = jasmine
            .createSpy("getGettextProvider")
            .and.returnValue({ gettext: () => "" });
        rewire$getGettextProvider(getGettextProvider);

        post = jasmine.createSpy("tlp.post");
        rewire$post(post);

        // TUS exports as CommonJS, so rewire does not seem to work
        spyOn(TUS, "Upload");

        options.ckeditor_instance = jasmine.createSpyObj("ckeditor_instance", ["fire"]);
        loader = jasmine.createSpyObj("fileLoader", ["changeStatus"]);
        loader.file = {
            name: "pentacyanic.jpg",
            type: "image/jpg"
        };
        file_upload_event = {
            data: {
                fileLoader: loader
            },
            stop: jasmine.createSpy("event.stop")
        };
        options.onStartCallback = jasmine.createSpy("onStartCallback");
        options.onErrorCallback = jasmine.createSpy("onErrorCallback");
        options.onSuccessCallback = jasmine.createSpy("onSuccessCallback");
        options.max_size_upload = 100;
    });

    function handlerFactory() {
        return buildFileUploadHandler(options);
    }

    afterEach(() => {
        restoreGettext();
        restoreTLP();
    });

    describe(`buildFileUploadHandler() - when a fileUploadRequest event is dispatched`, () => {
        it(`the event will be stopped`, async () => {
            mockFetchSuccess(post, {
                return_json: {
                    id: 71,
                    download_href: "https://example.com/extenuator"
                }
            });

            const handler = handlerFactory();
            await handler(file_upload_event);

            expect(file_upload_event.stop).toHaveBeenCalled();
        });

        it(`when the file size is above the max upload size,
                then the loader will show an error`, async () => {
            options.max_size_upload = 100;
            loader.file.size = 1024;

            const handler = handlerFactory();
            await handler(file_upload_event);

            expect(loader.changeStatus).toHaveBeenCalledWith("error");
        });

        describe(`when the file size is ok`, () => {
            beforeEach(() => {
                options.max_size_upload = 1024;
                loader.file = {
                    size: 1024
                };
            });

            it(`will post to the loader's uploadUrl`, async () => {
                loader.fileName = "pulpitism.png";
                loader.uploadUrl = "https://example.com/upload_url";
                loader.file.type = "image/png";
                mockFetchSuccess(post, {
                    return_json: {
                        id: 25,
                        download_url: "https://example.com/download_url"
                    }
                });

                const handler = handlerFactory();
                await handler(file_upload_event);

                expect(post).toHaveBeenCalledWith("https://example.com/upload_url", {
                    headers: { "content-type": "application/json" },
                    body: JSON.stringify({
                        name: "pulpitism.png",
                        file_size: 1024,
                        file_type: "image/png"
                    })
                });
            });

            describe(`when the POST endpoint returns an error`, () => {
                it(`will call the Error callback`, async () => {
                    post.and.returnValue(Promise.reject({}));

                    const handler = handlerFactory();
                    try {
                        await handler(file_upload_event);
                        fail("Promise should be rejected on error");
                    } catch (exception) {
                        expect(options.onErrorCallback).toHaveBeenCalled();
                        expect(loader.changeStatus).toHaveBeenCalledWith("error");
                        expect(loader.message).toBeDefined();
                    }
                });

                it(`when the error is translated, then it will show the translated error`, async () => {
                    mockFetchError(post, {
                        error_json: {
                            error: {
                                i18n_error_message: "Problème durant le téléversement"
                            }
                        }
                    });

                    const handler = handlerFactory();
                    try {
                        await handler(file_upload_event);
                        fail("Promise should be rejected on error");
                    } catch (exception) {
                        expect(loader.message).toEqual("Problème durant le téléversement");
                        expect(loader.changeStatus).toHaveBeenCalledWith("error");
                    }
                });

                it(`when the error is not translated, then it will show its message`, async () => {
                    mockFetchError(post, {
                        error_json: {
                            error: {
                                message: "Untranslated error message"
                            }
                        }
                    });

                    const handler = handlerFactory();
                    try {
                        await handler(file_upload_event);
                        fail("Promise should be rejected on error");
                    } catch (exception) {
                        expect(loader.message).toEqual("Untranslated error message");
                        expect(loader.changeStatus).toHaveBeenCalledWith("error");
                    }
                });
            });

            describe(`when the POST endpoint does not return an upload_href`, () => {
                beforeEach(() => {
                    mockFetchSuccess(post, {
                        return_json: {
                            id: 71,
                            download_href: "https://example.com/download_url"
                        }
                    });
                });

                it(`will mark the upload as successful in the loader`, async () => {
                    loader.file.name = "pentacyanic.jpg";

                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(loader.uploaded).toEqual(1);
                    expect(loader.fileName).toEqual("pentacyanic.jpg");
                    expect(loader.url).toEqual("https://example.com/download_url");
                    expect(loader.changeStatus).toHaveBeenCalledWith("uploaded");
                });

                it(`will stop CKEditor from breaking artifact display with width & height`, async () => {
                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(loader.responseData).toEqual({
                        width: " ",
                        height: " "
                    });
                });

                it(`will call the Success callback with the returned id and download_href`, async () => {
                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(options.onSuccessCallback).toHaveBeenCalledWith(
                        71,
                        "https://example.com/download_url"
                    );
                });
            });

            describe(`when the POST endpoint returns an upload_href`, () => {
                beforeEach(() => {
                    mockFetchSuccess(post, {
                        return_json: {
                            id: 23,
                            download_href: "https://example.com/download_url",
                            upload_href: "https://example.com/postloitic"
                        }
                    });
                });

                it(`will call the Start callback`, async () => {
                    TUS.Upload.and.callFake((file, config) => ({
                        start: () => config.onSuccess()
                    }));

                    const handler = handlerFactory();
                    await handler(file_upload_event);

                    expect(options.onStartCallback).toHaveBeenCalled();
                });

                describe(`will create and start the TUS uploader`, () => {
                    it(`and when the uploader receives the onProgress event,
                            it will update the loader with the bytes send and total`, async () => {
                        loader.update = jasmine.createSpy("loader.update");
                        TUS.Upload.and.callFake((file, config) => ({
                            start: () => {
                                config.onProgress(256, 1024);
                                expect(loader.uploadTotal).toEqual(1024);
                                expect(loader.uploaded).toEqual(256);
                                expect(loader.update).toHaveBeenCalled();

                                config.onSuccess();
                            }
                        }));
                        const handler = handlerFactory();
                        await handler(file_upload_event);
                    });

                    describe(`and when the uploader receives the onSuccess event`, () => {
                        beforeEach(() => {
                            TUS.Upload.and.callFake((file, config) => ({
                                start: () => config.onSuccess()
                            }));
                        });

                        it(`will mark the upload as successful in the loader`, async () => {
                            loader.file.name = "pentacyanic.jpg";

                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(loader.uploaded).toEqual(1);
                            expect(loader.fileName).toEqual("pentacyanic.jpg");
                            expect(loader.url).toEqual("https://example.com/download_url");
                            expect(loader.changeStatus).toHaveBeenCalledWith("uploaded");
                        });

                        it(`will stop CKEditor from breaking artifact display with width & height`, async () => {
                            const handler = handlerFactory();
                            await handler(file_upload_event);

                            expect(loader.responseData).toEqual({
                                width: " ",
                                height: " "
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
                                "https://example.com/download_url"
                            );
                        });
                    });

                    describe(`and when the uploader receives the onError event`, () => {
                        let error;
                        beforeEach(() => {
                            TUS.Upload.and.callFake((file, config) => ({
                                start: () => config.onError(error)
                            }));
                        });

                        it(`when CKEditor has a dedicated httpError message,
                                then it will show it`, async () => {
                            error = { originalRequest: { status: 403 } };
                            loader.lang = {
                                filetools: {
                                    httpError403: "Translated error message"
                                }
                            };

                            const handler = handlerFactory();
                            try {
                                await handler(file_upload_event);
                                fail("Promise should be rejected on error");
                            } catch (exception) {
                                expect(loader.message).toEqual("Translated error message");
                            }
                        });

                        it(`when CKEditor has no dedicated httpError message,
                                then it will show a generic message from CKEditor`, async () => {
                            error = { originalRequest: { status: 500 } };
                            loader.lang = {
                                filetools: {
                                    httpError: "Error %1"
                                }
                            };

                            const handler = handlerFactory();
                            try {
                                await handler(file_upload_event);
                                fail("Promise should be rejected on error");
                            } catch (exception) {
                                expect(loader.message).toEqual("Error 500");
                            }
                        });

                        it(`will mark the upload as failed in the loader`, async () => {
                            loader.lang = {
                                filetools: {
                                    httpError: ""
                                }
                            };
                            const handler = handlerFactory();
                            try {
                                await handler(file_upload_event);
                                fail("Promise should be rejected on error");
                            } catch (exception) {
                                expect(loader.changeStatus).toHaveBeenCalledWith("error");
                            }
                        });
                    });
                });
            });
        });
    });
});
