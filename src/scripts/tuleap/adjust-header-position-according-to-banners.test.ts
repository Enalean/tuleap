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

import { adjustHeaderPositionAccordingToBanners } from "./adjust-header-position-according-to-banners";
import { dimension } from "@shopify/jest-dom-mocks";

describe("adjustHeaderPositionAccordingToBanners", () => {
    let doc: Document;
    let platform_banner: HTMLElement;
    let project_banner: HTMLElement;
    let header: HTMLElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        header = doc.createElement("header");
        doc.body.appendChild(header);

        project_banner = doc.createElement("div");
        doc.body.appendChild(project_banner);

        platform_banner = doc.createElement("div");
        doc.body.appendChild(platform_banner);

        dimension.mock({
            offsetHeight: (element) => {
                if (element === project_banner) {
                    return 200;
                }

                if (element === platform_banner) {
                    return 150;
                }

                if (element === header) {
                    return 50;
                }

                return 0;
            },
        });
    });

    afterEach(() => {
        dimension.restore();
    });

    it(`Given platform banner is not defined,
        And project banner is not defined,
        Then header position is left untouched`, () => {
        adjustHeaderPositionAccordingToBanners(header, null, null, 10);

        expect(header.style.top).toBe("");
    });

    describe(`Given platform banner is not defined and project banner is defined`, () => {
        beforeEach(() => {
            project_banner = doc.createElement("div");
            doc.body.appendChild(project_banner);
        });

        it(`When project banner is hidden
            Then header position is left untouched`, () => {
            project_banner.classList.add("project-banner-hidden");

            adjustHeaderPositionAccordingToBanners(header, null, project_banner, 10);

            expect(header.style.top).toBe("");
        });

        it(`When project banner is displayed,
            Then header position is just below the project banner,
            minus the scroll so that the header is glued to the banner`, () => {
            adjustHeaderPositionAccordingToBanners(header, null, project_banner, 10);

            expect(header.style.top).toBe("190px");
        });

        it(`When project banner is displayed,
            And header is marked as pinned,
            Then its position is at the top of the viewport`, () => {
            header.classList.add("pinned");

            adjustHeaderPositionAccordingToBanners(header, null, project_banner, 10);

            expect(header.style.top).toBe("0px");
        });
    });

    describe(`Given platform banner is defined but hidden`, () => {
        beforeEach(() => {
            platform_banner.classList.add("platform-banner-hidden");
        });

        it(`And project banner is not defined
            Then header position is at the top of the viewport`, () => {
            adjustHeaderPositionAccordingToBanners(header, platform_banner, null, 10);

            expect(header.style.top).toBe("0px");
        });

        it(`And project banner is defined but is hidden
            Then header position is at the top of the viewport`, () => {
            project_banner.classList.add("project-banner-hidden");

            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("0px");
        });

        it(`And project banner is defined and displayed,
            Then header position is just below the project banner,
            minus the scroll so that the header is glued to the banner`, () => {
            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("190px");
        });

        it(`And project banner is defined and displayed,
            And header is marked as pinned,
            Then its position is at the top of the viewport`, () => {
            header.classList.add("pinned");

            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("0px");
        });
    });

    describe(`Given platform banner is defined and displayed`, () => {
        it(`And project banner is not defined
            Then header position is just below the platform banner`, () => {
            adjustHeaderPositionAccordingToBanners(header, platform_banner, null, 10);

            expect(header.style.top).toBe("150px");
        });

        it(`And project banner is not defined,
            And header is marked as pinned,
            Then its position is just below the platform banner`, () => {
            header.classList.add("pinned");

            adjustHeaderPositionAccordingToBanners(header, platform_banner, null, 10);

            expect(header.style.top).toBe("150px");
        });

        it(`And project banner is defined but is hidden
            Then header position is just below the platform banner`, () => {
            project_banner.classList.add("project-banner-hidden");

            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("150px");
        });

        it(`And project banner is defined and displayed,
            And header is marked as pinned,
            Then its position is just below the platform banner`, () => {
            project_banner.classList.add("project-banner-hidden");
            header.classList.add("pinned");

            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("150px");
        });

        it(`And project banner is defined and displayed,
            Then header position is inside the platform banner`, () => {
            adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, 10);

            expect(header.style.top).toBe("92px");
        });
    });
});
