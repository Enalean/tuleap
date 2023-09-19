/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

import "@tuleap/copy-to-clipboard";

export function setupEmailCopyModalInteractions(
    mount_point: Document,
    show_modal: (target: HTMLElement) => void,
): void {
    const reply_link = mount_point.querySelector(".email-tracker-reply");
    const reply_target = mount_point.getElementById("reply-by-mail-modal-info");
    if (reply_link && reply_target) {
        initModal(mount_point, reply_target, reply_link, show_modal);
    }

    const create_link = mount_point.querySelector("a[href='#create-by-mail-modal-info']");
    const create_target = mount_point.getElementById("create-by-mail-modal-info");
    if (create_link && create_target) {
        initModal(mount_point, create_target, create_link, show_modal);
    }
}

function initModal(
    mount_point: Document,
    target: HTMLElement,
    trigger: Element,
    show_modal: (target: HTMLElement) => void,
): void {
    trigger.addEventListener("click", (event: Event): void => {
        event.preventDefault();
        show_modal(target);
    });

    mount_point
        .querySelectorAll("copy-to-clipboard.tracker-email-copy-to-clipboard")
        .forEach((element: Element): void => {
            element.addEventListener("copied-to-clipboard", () => {
                const info = target.querySelector(".tracker-email-copy-to-clipboard-info");
                if (info) {
                    info.classList.add("shown");
                    setTimeout(() => info.classList.remove("shown"), 2000);
                }
            });
        });
}
