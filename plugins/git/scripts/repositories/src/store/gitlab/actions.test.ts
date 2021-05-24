/**
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
import type { ActionContext } from "plugins/document/node_modules/vuex/types";
import type { GitlabState } from "./state";
import type { GitLabCredentials, GitLabDataWithTokenPayload, GitLabRepository } from "../../type";
import type { Modal } from "tlp";

describe("action", () => {
    describe("getGitlabProjectList", () => {
        let context: ActionContext<GitlabState, GitlabState>;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            } as unknown as ActionContext<GitlabState, GitlabState>;
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
                            get: () => "1",
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    } as unknown as Response);
                })
            );
            const credentials: GitLabCredentials = {
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
                            get: () => "2",
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    } as unknown as Response);
                })
            );
            const credentials: GitLabCredentials = {
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
                    } as Response);
                })
            );
            const credentials: GitLabCredentials = {
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
        let context: ActionContext<GitlabState, GitlabState>;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            } as unknown as ActionContext<GitlabState, GitlabState>;
        });

        it("When api is called, Then url is formatted", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        get: () => "1",
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    } as unknown as Response);
                })
            );
            const credentials: GitLabCredentials = {
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
                    } as unknown as Response);
                })
            );
            const credentials: GitLabCredentials = {
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
        const context: ActionContext<GitlabState, GitlabState> = {} as ActionContext<
            GitlabState,
            GitlabState
        >;

        it("When api is called, Then url is formatted", async () => {
            const patchGitlabRepository = jest.spyOn(gitlab_querier, "patchGitlabRepository");

            patchGitlabRepository.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        get: () => "1",
                        status: 200,
                    } as unknown as Response);
                })
            );

            const payload: GitLabDataWithTokenPayload = {
                gitlab_api_token: "AZERTY1234",
                gitlab_integration_id: 10,
            };

            await updateBotApiTokenGitlab(context, payload);

            expect(patchGitlabRepository).toHaveBeenCalledWith(10, {
                update_bot_api_token: {
                    gitlab_api_token: payload.gitlab_api_token,
                },
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

            const payload: GitLabDataWithTokenPayload = {
                gitlab_api_token: "AZERTY1234",
                gitlab_integration_id: 10,
            };

            await expect(updateBotApiTokenGitlab(context, payload)).rejects.toEqual({
                status: 401,
            });

            expect(patchGitlabRepository).toHaveBeenCalledWith(10, {
                update_bot_api_token: {
                    gitlab_api_token: payload.gitlab_api_token,
                },
            });
        });
    });

    describe("showEditAccessTokenGitlabRepositoryModal", () => {
        let context: ActionContext<GitlabState, GitlabState> = {} as ActionContext<
            GitlabState,
            GitlabState
        >;
        beforeEach(() => {
            const modal: Modal = { toggle: jest.fn() } as unknown as Modal;
            context = {
                commit: jest.fn(),
                state: {
                    edit_access_token_gitlab_repository_modal: modal,
                } as GitlabState,
            } as unknown as ActionContext<GitlabState, GitlabState>;
        });

        const repository = { id: 5 } as GitLabRepository;

        it("When modal should be open, Then repository is set and modal is opened", () => {
            showEditAccessTokenGitlabRepositoryModal(context, repository);
            expect(context.commit).toHaveBeenCalledWith(
                "setEditAccessTokenGitlabRepository",
                repository
            );
            if (!context.state.edit_access_token_gitlab_repository_modal) {
                throw new Error("Modal is null");
            }
            expect(
                context.state.edit_access_token_gitlab_repository_modal.toggle
            ).toHaveBeenCalled();
        });
    });
});
