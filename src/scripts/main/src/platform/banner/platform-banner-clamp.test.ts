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
    allowUnclampingPlatformBannerMessage,
    PLATFORM_BANNER_MESSAGE_ID,
    PLATFORM_BANNER_MESSAGE_CLAMP_CLASS,
    PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
} from "./platform-banner-clamp";

describe("Platform banner clamp", () => {
    it("Does not crash when the banner message can not be found", () => {
        allowUnclampingPlatformBannerMessage(document);
    });

    function getLocalDocumentWithPlatformBannerMessage(): {
        document: Document;
        message: HTMLElement;
    } {
        const local_document = document.implementation.createHTMLDocument();
        const message_element = local_document.createElement("p");
        message_element.setAttribute("id", PLATFORM_BANNER_MESSAGE_ID);
        message_element.setAttribute("class", PLATFORM_BANNER_MESSAGE_CLAMP_CLASS);
        message_element.textContent = "My platform banner message";
        local_document.body.appendChild(message_element);

        return { document: local_document, message: message_element };
    }

    it("Unclamps the platform banner when the user clicks on it", () => {
        const local_document_with_banner = getLocalDocumentWithPlatformBannerMessage();
        allowUnclampingPlatformBannerMessage(local_document_with_banner.document);
        expect(local_document_with_banner.message.classList).toContain(
            PLATFORM_BANNER_MESSAGE_CLAMP_CLASS,
        );
        local_document_with_banner.message.click();
        expect(local_document_with_banner.message.classList).not.toContain(
            PLATFORM_BANNER_MESSAGE_CLAMP_CLASS,
        );
    });

    it("Shows an hint to the user the message can displayed fully if it is clamped", () => {
        const local_document_with_banner = getLocalDocumentWithPlatformBannerMessage();
        const banner_message = local_document_with_banner.message;
        jest.spyOn(banner_message, "scrollWidth", "get").mockReturnValue(150);
        jest.spyOn(banner_message, "clientWidth", "get").mockReturnValue(100);
        allowUnclampingPlatformBannerMessage(local_document_with_banner.document);

        expect(banner_message.classList).toContain(PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    });

    it("Does not show an hint to the user the message can displayed fully if it not clamped", () => {
        const local_document_with_banner = getLocalDocumentWithPlatformBannerMessage();
        const banner_message = local_document_with_banner.message;
        jest.spyOn(banner_message, "scrollWidth", "get").mockReturnValue(100);
        jest.spyOn(banner_message, "clientWidth", "get").mockReturnValue(150);
        allowUnclampingPlatformBannerMessage(local_document_with_banner.document);

        expect(banner_message.classList).not.toContain(
            PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
        );
    });

    it("Checks if an hint needs to be displayed to the user that the message is clamped after a resize", () => {
        jest.spyOn(window, "requestAnimationFrame").mockImplementation((cb) => {
            cb(1);
            return 1;
        });
        const local_document_with_banner = getLocalDocumentWithPlatformBannerMessage();
        const banner_message = local_document_with_banner.message;
        allowUnclampingPlatformBannerMessage(local_document_with_banner.document);

        expect(banner_message.classList).not.toContain(
            PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
        );

        jest.spyOn(banner_message, "scrollWidth", "get").mockReturnValue(150);
        jest.spyOn(banner_message, "clientWidth", "get").mockReturnValue(100);

        window.dispatchEvent(new Event("resize"));

        expect(banner_message.classList).toContain(PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    });

    it("Checks if an hint needs to be displayed to the user when classes on the message parent element changes", () => {
        return new Promise<void>((done) => {
            const local_document_with_banner = getLocalDocumentWithPlatformBannerMessage();
            const banner_message = local_document_with_banner.message;
            allowUnclampingPlatformBannerMessage(local_document_with_banner.document);

            expect(banner_message.classList).not.toContain(
                PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
            );

            jest.spyOn(banner_message, "scrollWidth", "get").mockReturnValue(150);
            jest.spyOn(banner_message, "clientWidth", "get").mockReturnValue(100);

            if (
                banner_message.parentElement !== null &&
                banner_message.parentElement.parentElement !== null
            ) {
                banner_message.parentElement.parentElement.classList.add(
                    "new-class-on-the-platform-banner",
                );
            }

            setTimeout(() => {
                expect(banner_message.classList).toContain(
                    PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
                );
                done();
            });
        });
    });
});
