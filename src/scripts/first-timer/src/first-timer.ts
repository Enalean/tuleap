/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import "./first-timer.scss";
import confetti from "canvas-confetti";
document.addEventListener("DOMContentLoaded", () => {
    const modal_element = document.getElementById("first-timer-success-modal");
    if (modal_element) {
        const modal = createModal(modal_element);
        modal.show();
        launchSomeConfettis(modal);
    }
});

function launchSomeConfettis(modal: Modal): void {
    const confetti_canvas = document.createElement("canvas");
    confetti_canvas.classList.add("first-timer-success-confetti-canvas");
    document.body.appendChild(confetti_canvas);

    const fire = confetti.create(confetti_canvas, {
        useWorker: false,
        resize: true,
    });

    modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, reset);

    const tlp_main_color = getComputedStyle(document.documentElement).getPropertyValue(
        "--tlp-main-color",
    );
    const one_quarter_white_three_quarters_main_color =
        tlp_main_color.length > 0
            ? { colors: [tlp_main_color, tlp_main_color, tlp_main_color, "#ffffff"] }
            : {};

    const default_options = {
        disableForReducedMotion: true,
        particleCount: 200,
        spread: 55,
        ...one_quarter_white_three_quarters_main_color,
    };

    const bottom_left_canon = {
        ...default_options,
        angle: 60,
        origin: { x: -0.05, y: 1.05 },
    };
    const bottom_right_canon = {
        ...default_options,
        angle: 120,
        origin: { x: 1.05, y: 1.05 },
    };

    [50, 100, 150].forEach((startVelocity) => {
        const drift_according_to_velocity = startVelocity / 200;

        fire({
            ...bottom_left_canon,
            drift: drift_according_to_velocity,
            startVelocity,
        });
        fire({
            ...bottom_right_canon,
            drift: -drift_according_to_velocity,
            startVelocity,
        });
    });

    function reset(): void {
        fire.reset();
        confetti_canvas.remove();
    }

    setTimeout(() => {
        reset();
    }, 7000);
}
