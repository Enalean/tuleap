/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import * as gitlab_querier from "../../gitlab/gitlab-api-querier";
import {
    getGitlabProjectList,
    getGitlabRepositoryFromId,
    updateBotApiTokenGitlab,
    showEditAccessTokenGitlabRepositoryModal,
} from "./actions";

describe("action", () => {
    describe("getGitlabProjectList", () => {
        let context;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            };
        });

        it("When api is called, Then url is formatted", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 1,
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            expect(await getGitlabProjectList(context, credentials)).toEqual([{ id: 10 }]);
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url:
                    "https://example/api/v4/projects?membership=true&per_page=20&min_access_level=40",
                token: "azerty1234",
            });
        });

        it("When there is 2 pages, Then api is called twice", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 2,
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            expect(await getGitlabProjectList(context, credentials)).toEqual([
                { id: 10 },
                { id: 10 },
            ]);
            expect(getAsyncGitlabRepositoryList).toBeCalledTimes(2);
        });

        it("When en error retrieved from api, Then an error is thrown", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        status: 401,
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            await expect(getGitlabProjectList(context, credentials)).rejects.toEqual(new Error());
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url:
                    "https://example/api/v4/projects?membership=true&per_page=20&min_access_level=40",
                token: "azerty1234",
            });
        });
    });

    describe("getGitlabRepositoryFromId", () => {
        let context;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            };
        });

        it("When api is called, Then url is formatted", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 1,
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            expect(await getGitlabRepositoryFromId(context, { credentials, id: 12 })).toEqual([
                { id: 10 },
            ]);
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url: "https://example/api/v4/projects/12",
                token: "azerty1234",
            });
        });

        it("When an error is retrieved from api, Then an error is thrown", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        status: 401,
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            await expect(
                getGitlabRepositoryFromId(context, { credentials, id: 12 })
            ).rejects.toEqual(new Error());
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url: "https://example/api/v4/projects/12",
                token: "azerty1234",
            });
        });
    });

    describe("updateBotApiTokenGitlab", () => {
        const context = {};

        it("When api is called, Then url is formatted", async () => {
            const patchGitlabRepository = jest.spyOn(gitlab_querier, "patchGitlabRepository");

            patchGitlabRepository.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 1,
                        },
                        status: 200,
                    });
                })
            );

            const credentials = {
                gitlab_bot_api_token: "AZERTY1234",
                gitlab_repository_id: 10,
                gitlab_repository_url: "https://example.com",
            };

            await updateBotApiTokenGitlab(context, credentials);

            expect(patchGitlabRepository).toHaveBeenCalledWith({
                update_bot_api_token: credentials,
            });
        });

        it("When an error is retrieved from api, Then an error is thrown", async () => {
            const patchGitlabRepository = jest.spyOn(gitlab_querier, "patchGitlabRepository");

            patchGitlabRepository.mockReturnValue(
                new Promise((resolve, reject) => {
                    reject({
                        status: 401,
                    });
                })
            );

            const credentials = {
                gitlab_bot_api_token: "AZERTY1234",
                gitlab_repository_id: 10,
                gitlab_repository_url: "https://example.com",
            };

            await expect(updateBotApiTokenGitlab(context, credentials)).rejects.toEqual({
                status: 401,
            });

            expect(patchGitlabRepository).toHaveBeenCalledWith({
                update_bot_api_token: credentials,
            });
        });
    });

    describe("showEditAccessTokenGitlabRepositoryModal", () => {
        let context;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {
                    edit_access_token_gitlab_repository_modal: { toggle: jest.fn() },
                },
            };
        });

        const repository = { id: 5 };

        it("When modal should be open, Then repository is set and modal is opened", () => {
            showEditAccessTokenGitlabRepositoryModal(context, repository);
            expect(context.commit).toHaveBeenCalledWith(
                "setEditAccessTokenGitlabRepository",
                repository
            );
            expect(
                context.state.edit_access_token_gitlab_repository_modal.toggle
            ).toHaveBeenCalled();
        });
    });
});
