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

import {
    PROJECT_BANNER_NAVBAR_ID,
    PROJECT_BANNER_MESSAGE_CLOSE_BUTTON_ID,
    PROJECT_BANNER_HIDDEN_CLASS,
    allowToHideAndShowProjectBanner,
    PROJECT_BANNER_VISIBLE_GLOBAL_CLASS,
    PROJECT_NAVBAR_TO_BANNER_CLASS,
} from "./project-banner-show-hide";

const USER_ID = "1200";
const PROJECT_ID = "102";

describe("Show and hide project banner", () => {
    function getTlpPatchSpy(): jest.Mock<
        Promise<Response>,
        [string, RequestInit & { method?: "PATCH" }]
    > {
        return jest.fn();
    }

    function getLocalDocumentWithProjectBannerAndNavbarInformation(): {
        document: Document;
        project_banner: HTMLElement;
        close_button: HTMLElement;
        project_banner_navbar_information: HTMLElement;
    } {
        const local_document = document.implementation.createHTMLDocument();

        local_document.body.classList.add(PROJECT_BANNER_VISIBLE_GLOBAL_CLASS);

        const navbar = local_document.createElement("nav");
        const navbar_project_banner_info = local_document.createElement("div");
        navbar_project_banner_info.setAttribute("id", PROJECT_BANNER_NAVBAR_ID);
        navbar.classList.add(PROJECT_NAVBAR_TO_BANNER_CLASS);
        navbar.appendChild(navbar_project_banner_info);
        local_document.body.appendChild(navbar);

        const project_banner = local_document.createElement("div");
        const project_banner_close_button = local_document.createElement("i");
        project_banner_close_button.setAttribute("id", PROJECT_BANNER_MESSAGE_CLOSE_BUTTON_ID);
        project_banner_close_button.dataset.projectId = PROJECT_ID;
        project_banner_close_button.dataset.userId = USER_ID;
        project_banner.appendChild(project_banner_close_button);
        local_document.body.appendChild(project_banner);

        return {
            document: local_document,
            project_banner: project_banner,
            close_button: project_banner_close_button,
            project_banner_navbar_information: navbar_project_banner_info,
        };
    }

    it("Does not do anything when the close button is not present", () => {
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();

        local_document.project_banner.removeChild(local_document.close_button);
        allowToHideAndShowProjectBanner(local_document.document, getTlpPatchSpy());
    });

    it("Throws an error when the close button does not know the user ID", () => {
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();

        local_document.close_button.removeAttribute("data-user-id");

        expect(() => {
            allowToHideAndShowProjectBanner(local_document.document, getTlpPatchSpy());
        }).toThrow();
    });

    it("Throws an error when the close button does not know the project ID", () => {
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();

        local_document.close_button.removeAttribute("data-project-id");

        expect(() => {
            allowToHideAndShowProjectBanner(local_document.document, getTlpPatchSpy());
        }).toThrow();
    });

    it("Can hide and show the project banner", () => {
        const windowScrollToSpy = jest.spyOn(window, "scrollTo").mockImplementation();
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();
        const tlpPatchSpy = getTlpPatchSpy();

        allowToHideAndShowProjectBanner(local_document.document, tlpPatchSpy);

        local_document.close_button.click();
        expect(local_document.document.body.classList).not.toContain(
            PROJECT_BANNER_VISIBLE_GLOBAL_CLASS
        );
        expect(local_document.project_banner.classList).toContain(PROJECT_BANNER_HIDDEN_CLASS);
        expect(local_document.project_banner_navbar_information.classList).toContain(
            PROJECT_BANNER_HIDDEN_CLASS
        );
        expect(local_document.project_banner_navbar_information.classList).not.toContain(
            PROJECT_NAVBAR_TO_BANNER_CLASS
        );
        expect(tlpPatchSpy).toHaveBeenCalledWith(
            `/api/users/${USER_ID}/preferences`,
            expect.objectContaining({
                body: expect.stringContaining(PROJECT_ID),
            })
        );

        let click_event: Event | undefined;
        local_document.project_banner_navbar_information.addEventListener(
            "click",
            (event: Event) => {
                click_event = event;
            }
        );

        local_document.project_banner_navbar_information.click();
        expect(local_document.document.body.classList).toContain(
            PROJECT_BANNER_VISIBLE_GLOBAL_CLASS
        );
        expect(local_document.project_banner.classList).not.toContain(PROJECT_BANNER_HIDDEN_CLASS);
        expect(local_document.project_banner_navbar_information.classList).not.toContain(
            PROJECT_BANNER_HIDDEN_CLASS
        );
        expect(local_document.project_banner_navbar_information.classList).toContain(
            PROJECT_NAVBAR_TO_BANNER_CLASS
        );
        expect(windowScrollToSpy).toHaveBeenCalledTimes(1);
        expect(click_event).toBeDefined();
        if (click_event !== undefined) {
            expect(click_event.defaultPrevented).toBe(true);
        }
    });

    it("hides the navbar to banner element when the user scrolls", () => {
        jest.spyOn(window, "requestAnimationFrame").mockImplementation((cb) => {
            cb(1);
            return 1;
        });
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();

        allowToHideAndShowProjectBanner(local_document.document, getTlpPatchSpy());

        expect(local_document.project_banner_navbar_information.classList).toContain(
            PROJECT_NAVBAR_TO_BANNER_CLASS
        );

        Object.defineProperty(window, "scrollY", { get: () => 150 });
        window.dispatchEvent(new Event("scroll"));

        expect(local_document.project_banner_navbar_information.classList).not.toContain(
            PROJECT_NAVBAR_TO_BANNER_CLASS
        );
    });

    it("should not the navbar to banner element on first load when the user is not at the top of the page", () => {
        jest.spyOn(window, "requestAnimationFrame").mockImplementation((cb) => {
            cb(1);
            return 1;
        });
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();

        Object.defineProperty(window, "scrollY", { get: () => 150 });

        allowToHideAndShowProjectBanner(local_document.document, getTlpPatchSpy());

        expect(local_document.project_banner_navbar_information.classList).not.toContain(
            PROJECT_NAVBAR_TO_BANNER_CLASS
        );
    });
});
