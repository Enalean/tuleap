/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

/* global jQuery:readonly */
(function ($) {
    let currentRequest = null;

    function checkPassword() {
        if (currentRequest !== null) {
            currentRequest.abort();
        }
        currentRequest = $.post("/include/check_pw.php", {
            form_pw: $(this).val(),
        }).done(function (data) {
            if (toggleErrorMessages(data)) {
                setRobustnessToBad();
            } else {
                setRobustnessToGood();
            }
            currentRequest = null;
        });
    }

    function toggleErrorMessages(data) {
        var has_errors = false;
        window.password_validators.forEach(function (key) {
            $(".password_validator_msg_" + key).each(function () {
                if (data.indexOf(key) >= 0) {
                    has_errors = true;
                    $(this).addClass("tlp-text-danger").removeClass("tlp-text-success");

                    $(this)
                        .find("> i")
                        .addClass("fa-times")
                        .addClass("password-strategy-bad")
                        .removeClass("fa-check")
                        .removeClass("password-strategy-good");
                } else {
                    $(this).addClass("tlp-text-success").removeClass("tlp-text-danger");

                    $(this)
                        .find("> i")
                        .addClass("fa-check")
                        .addClass("password-strategy-good")
                        .removeClass("fa-times")
                        .removeClass("password-strategy-bad");
                }
            });
        });

        return has_errors;
    }

    function setRobustnessToGood() {
        $(".robustness").removeClass("bad");
        $(".robustness").addClass("good");

        $(".robustness > .password-strategy-bad").hide();
        $(".robustness > .password-strategy-good").show();
        $(".robustness .fa-circle-o-notch").removeClass("fa-circle-o-notch fa-spin");
    }

    function setRobustnessToBad() {
        $(".robustness").removeClass("good");
        $(".robustness").addClass("bad");

        $(".robustness > .password-strategy-bad").show();
        $(".robustness > .password-strategy-good").hide();
        $(".robustness .fa-circle-o-notch").removeClass("fa-circle-o-notch fa-spin");
    }

    /**
     * Simplified version of debounce function of Underscore.js
     *
     * @see http://underscorejs.org/#debounce
     */
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this,
                args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                timeout = null;
                func.apply(context, args);
            }, wait);
        };
    }

    $(document).ready(function () {
        setRobustnessToBad();

        const debouncedCheckPassword = debounce(checkPassword, 300);

        $("#form_pw").on("paste keyup", debouncedCheckPassword);
        $("#form_pw").on("paste keyup", function () {
            $(".robustness .fa-times, .robustness .fa-check")
                .removeClass("fa-times fa-check")
                .addClass("fa-circle-o-notch fa-spin");
        });
        $("#form_pw").on("focus", function () {
            $(".account-security-password-robustness").removeClass(
                "account-security-password-robustness-hidden"
            );
        });
    });
})(jQuery);
