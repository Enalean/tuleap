/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { CampaignState } from "./type";
import { Campaign } from "../../type";
import {
    addCampaigns,
    addNewCampaign,
    beginLoadingCampaigns,
    endLoadingCampaigns,
    loadingErrorHasBeenCatched,
    refreshingloadingErrorHasBeenCatched,
    removeHasRefreshingErrorFlag,
    updateCampaignAfterCreation,
} from "./campaign-mutations";

jest.useFakeTimers();

describe("Campaign state mutations", () => {
    it("beginLoadingCampaigns", () => {
        const state: CampaignState = {
            is_loading: false,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [],
        };

        beginLoadingCampaigns(state);

        expect(state.is_loading).toBe(true);
    });

    it("endLoadingCampaigns", () => {
        const state: CampaignState = {
            is_loading: true,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [],
        };

        endLoadingCampaigns(state);

        expect(state.is_loading).toBe(false);
    });

    it("addCampaigns", () => {
        const state: CampaignState = {
            is_loading: true,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [{ id: 1 } as Campaign],
        };

        addCampaigns(state, [{ id: 2 }, { id: 3 }] as Campaign[]);

        expect(state.campaigns.length).toBe(3);
    });

    it("loadingErrorHasBeenCatched", () => {
        const state: CampaignState = {
            is_loading: true,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [],
        };

        loadingErrorHasBeenCatched(state);

        expect(state.has_loading_error).toBe(true);
    });

    it("refreshingloadingErrorHasBeenCatched", () => {
        const state: CampaignState = {
            is_loading: false,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [],
        };

        refreshingloadingErrorHasBeenCatched(state);

        expect(state.has_refreshing_error).toBe(true);
    });

    it("removeHasRefreshingErrorFlag", () => {
        const state: CampaignState = {
            is_loading: false,
            has_loading_error: false,
            has_refreshing_error: true,
            campaigns: [],
        };

        removeHasRefreshingErrorFlag(state);

        expect(state.has_refreshing_error).toBe(false);
    });

    it("adds new campaign at the beginning", () => {
        const state: CampaignState = {
            is_loading: false,
            has_loading_error: false,
            has_refreshing_error: false,
            campaigns: [{ id: 123 } as Campaign],
        };

        addNewCampaign(state, { id: 42 } as Campaign);

        expect(state.campaigns).toStrictEqual([{ id: 42 }, { id: 123 }] as Campaign[]);
    });

    describe("updateCampaignAfterCreation", () => {
        it("Throw error if campaign cannot be found", () => {
            const state: CampaignState = {
                is_loading: false,
                has_loading_error: false,
                has_refreshing_error: false,
                campaigns: [{ id: 123 } as Campaign],
            };

            expect(() => {
                updateCampaignAfterCreation(state, { id: 42 } as Campaign);
            }).toThrow();
        });

        it("store campaigns as just refreshed", () => {
            const state: CampaignState = {
                is_loading: false,
                has_loading_error: false,
                has_refreshing_error: false,
                campaigns: [{ id: 123 } as Campaign],
            };

            updateCampaignAfterCreation(state, { id: 123 } as Campaign);

            expect(state.campaigns).toStrictEqual([
                { id: 123, is_being_refreshed: false, is_just_refreshed: true },
            ]);
        });

        it("removes just refreshed flag after one second", () => {
            const state: CampaignState = {
                is_loading: false,
                has_loading_error: false,
                has_refreshing_error: false,
                campaigns: [{ id: 123 } as Campaign],
            };

            updateCampaignAfterCreation(state, { id: 123 } as Campaign);
            jest.advanceTimersByTime(1000);

            expect(state.campaigns).toStrictEqual([
                { id: 123, is_being_refreshed: false, is_just_refreshed: false },
            ]);
        });
    });
});
