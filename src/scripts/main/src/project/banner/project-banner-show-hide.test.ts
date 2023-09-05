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
    PROJECT_BANNER_MESSAGE_CLOSE_BUTTON_ID,
    PROJECT_BANNER_HIDDEN_CLASS,
    allowToHideAndShowProjectBanner,
    PROJECT_BANNER_VISIBLE_GLOBAL_CLASS,
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
        project_sidebar: HTMLElement;
    } {
        const local_document = document.implementation.createHTMLDocument();

        local_document.body.classList.add(PROJECT_BANNER_VISIBLE_GLOBAL_CLASS);

        const project_sidebar = local_document.createElement("tuleap-project-sidebar");
        local_document.body.appendChild(project_sidebar);

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
            project_sidebar,
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

    it("Can hide and show the project banner when using the tuleap-project-sidebar container", () => {
        const windowScrollToSpy = jest.spyOn(window, "scrollTo").mockImplementation();
        const local_document = getLocalDocumentWithProjectBannerAndNavbarInformation();
        const tlpPatchSpy = getTlpPatchSpy();

        allowToHideAndShowProjectBanner(local_document.document, tlpPatchSpy);

        local_document.close_button.click();
        expect(local_document.document.body.classList).not.toContain(
            PROJECT_BANNER_VISIBLE_GLOBAL_CLASS,
        );
        expect(local_document.project_banner.classList).toContain(PROJECT_BANNER_HIDDEN_CLASS);
        expect(tlpPatchSpy).toHaveBeenCalledWith(
            `/api/users/${USER_ID}/preferences`,
            expect.objectContaining({
                body: expect.stringContaining(PROJECT_ID),
            }),
        );

        local_document.project_sidebar.dispatchEvent(new CustomEvent("show-project-announcement"));
        expect(local_document.document.body.classList).toContain(
            PROJECT_BANNER_VISIBLE_GLOBAL_CLASS,
        );
        expect(local_document.project_banner.classList).not.toContain(PROJECT_BANNER_HIDDEN_CLASS);
        expect(windowScrollToSpy).toHaveBeenCalledTimes(1);
    });
});
