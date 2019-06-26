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
(function($) {
    let currentRequest = null;

    function checkPassword() {
        if (currentRequest !== null) {
            currentRequest.abort();
        }
        currentRequest = $.post("/include/check_pw.php", {
            form_pw: $(this).val()
        }).done(function(data) {
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
        window.password_validators.forEach(function(key) {
            $(".password_validator_msg_" + key + " > i").each(function() {
                if (data.indexOf(key) >= 0) {
                    has_errors = true;
                    $(this)
                        .addClass("fa-times")
                        .addClass("password_strategy_bad")
                        .removeClass("fa-check")
                        .removeClass("password_strategy_good");
                } else {
                    $(this)
                        .addClass("fa-check")
                        .addClass("password_strategy_good")
                        .removeClass("fa-times")
                        .removeClass("password_strategy_bad");
                }
            });
        });

        return has_errors;
    }

    function setRobustnessToGood() {
        $(".robustness .password_strategy_bad").hide();
        $(".robustness .password_strategy_good").show();
        $(".robustness .password_validators_loading").hide();
    }

    function setRobustnessToBad() {
        $(".robustness .password_strategy_bad").show();
        $(".robustness .password_strategy_good").hide();
        $(".robustness .password_validators_loading").hide();
    }

    /**
     * Simplified version of debounce function of Underscore.js
     *
     * @see http://underscorejs.org/#debounce
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this,
                args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                timeout = null;
                func.apply(context, args);
            }, wait);
        };
    }

    $(document).ready(function() {
        setRobustnessToBad();

        const debouncedCheckPassword = debounce(checkPassword, 300);

        $("#form_pw").on("paste keyup", debouncedCheckPassword);
        $("#form_pw").on("paste keyup", function() {
            $(".robustness .password_validators_loading").show();
        });
    });
})(jQuery);
