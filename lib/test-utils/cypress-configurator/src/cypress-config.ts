/**
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

export const cypress_config = {
    reporter: "junit",
    reporterOptions: {
        mochaFile: "/output/e2e-results-[hash].xml",
    },
    screenshotsFolder: "/output/e2e-screenshots",
    videosFolder: "/output/e2e-videos",
    video: true,
    pageLoadTimeout: 120000,
    viewportWidth: 1366,
    viewportHeight: 768,
    trashAssetsBeforeRuns: false,
    e2e: {
        baseUrl: "https://tuleap",
    },
};
