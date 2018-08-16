/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

!(function($) {
    var did_you_mean = new Template(codendi.getText("register", "did_you_mean"));

    function displaySuggestion(element, suggestion) {
        var text = did_you_mean.evaluate(suggestion);

        getEmailSuggestionPanel(element)
            .html(text)
            .slideDown("fast");
    }

    function clearSuggestion(element) {
        getEmailSuggestionPanel(element).slideUp("fast");
    }

    function checkEmailOnUserInput() {
        var element = $(this);

        element.mailcheck({
            suggested: displaySuggestion,
            empty: clearSuggestion
        });
    }

    function userAcceptsSuggestion() {
        var element = $(this);

        element
            .parent(".email-suggestion")
            .slideUp("fast")
            .data("input_element")
            .val(element.text());
    }

    function getEmailSuggestionPanel(input_element) {
        var id = input_element.data("email-suggestion");

        if (!id) {
            return $();
        }

        return $("#" + id);
    }

    $(document).ready(function() {
        var element = $("input[type=email]");

        element.on("blur", checkEmailOnUserInput);

        getEmailSuggestionPanel(element)
            .data("input_element", element)
            .delegate(".suggested-email", "click", userAcceptsSuggestion);
    });
})(window.jQuery);
