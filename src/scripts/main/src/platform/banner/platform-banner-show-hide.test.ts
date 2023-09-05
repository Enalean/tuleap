/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
    PLATFORM_BANNER_NAVBAR_ID,
    PLATFORM_BANNER_MESSAGE_CLOSE_BUTTON_ID,
    PLATFORM_BANNER_HIDDEN_CLASS,
    PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS,
    allowToHideAndShowPlatformBanner,
} from "./platform-banner-show-hide";

const USER_ID = "1200";

describe("Show and hide platform banner", () => {
    function getTlpPatchSpy(): jest.Mock<
        Promise<Response>,
        [string, RequestInit & { method?: "PATCH" }]
    > {
        return jest.fn();
    }

    function getLocalDocumentWithPlatformBannerAndNavbarInformation(): {
        document: Document;
        platform_banner: HTMLElement;
        close_button: HTMLElement;
        platform_banner_navbar_information: HTMLElement;
    } {
        const local_document = document.implementation.createHTMLDocument();

        local_document.body.classList.add(PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS);

        const navbar = local_document.createElement("nav");
        const navbar_platform_banner_info = local_document.createElement("div");
        navbar_platform_banner_info.setAttribute("id", PLATFORM_BANNER_NAVBAR_ID);
        navbar.appendChild(navbar_platform_banner_info);
        local_document.body.appendChild(navbar);

        const platform_banner = local_document.createElement("div");
        const platform_banner_close_button = local_document.createElement("i");
        platform_banner_close_button.setAttribute("id", PLATFORM_BANNER_MESSAGE_CLOSE_BUTTON_ID);
        platform_banner_close_button.dataset.userId = USER_ID;
        platform_banner.appendChild(platform_banner_close_button);
        local_document.body.appendChild(platform_banner);

        return {
            document: local_document,
            platform_banner: platform_banner,
            close_button: platform_banner_close_button,
            platform_banner_navbar_information: navbar_platform_banner_info,
        };
    }

    it("Does not do anything when the close button is not present", () => {
        const local_document = getLocalDocumentWithPlatformBannerAndNavbarInformation();

        local_document.platform_banner.removeChild(local_document.close_button);
        allowToHideAndShowPlatformBanner(local_document.document, getTlpPatchSpy());
    });

    it("Throws an error when the close button does not know the user ID", () => {
        const local_document = getLocalDocumentWithPlatformBannerAndNavbarInformation();

        local_document.close_button.removeAttribute("data-user-id");

        expect(() => {
            allowToHideAndShowPlatformBanner(local_document.document, getTlpPatchSpy());
        }).toThrow();
    });

    it("Can hide and show the platform banner", () => {
        const windowScrollToSpy = jest.spyOn(window, "scrollTo").mockImplementation();
        const local_document = getLocalDocumentWithPlatformBannerAndNavbarInformation();
        const tlpPatchSpy = getTlpPatchSpy();

        allowToHideAndShowPlatformBanner(local_document.document, tlpPatchSpy);

        local_document.close_button.click();
        expect(local_document.document.body.classList).not.toContain(
            PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS,
        );
        expect(local_document.platform_banner.classList).toContain(PLATFORM_BANNER_HIDDEN_CLASS);
        expect(local_document.platform_banner_navbar_information.classList).not.toContain(
            PLATFORM_BANNER_HIDDEN_CLASS,
        );
        expect(tlpPatchSpy).toHaveBeenCalledWith(
            `/api/users/${USER_ID}/preferences`,
            expect.objectContaining({
                body: JSON.stringify({
                    key: "platform_banner",
                    value: "hidden",
                }),
            }),
        );

        let click_event: Event | undefined;
        local_document.platform_banner_navbar_information.addEventListener(
            "click",
            (event: Event) => {
                click_event = event;
            },
        );

        local_document.platform_banner_navbar_information.click();
        expect(local_document.document.body.classList).toContain(
            PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS,
        );
        expect(local_document.platform_banner.classList).not.toContain(
            PLATFORM_BANNER_HIDDEN_CLASS,
        );
        expect(local_document.platform_banner_navbar_information.classList).toContain(
            PLATFORM_BANNER_HIDDEN_CLASS,
        );
        expect(windowScrollToSpy).toHaveBeenCalledTimes(1);
        expect(click_event).toBeDefined();
        if (click_event === undefined) {
            throw new Error("Expected a click event to be received");
        }
        expect(click_event.defaultPrevented).toBe(true);
    });
});
