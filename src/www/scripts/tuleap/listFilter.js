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


/**
 * Handle lists filtering
 */

var tuleap  = tuleap || { };
tuleap.core = tuleap.core || { };

(function($) {
    /**
     * @see http://css-tricks.com/snippets/jquery/make-jquery-contains-case-insensitive/
     */
    (function addCaseInsensitiveContainsSelector(){
        // NEW selector
        $.expr[':'].caseInsensitiveContains = function(a, i, m) {
          return $(a).text().toUpperCase()
              .indexOf(m[3].toUpperCase()) >= 0;
        };
    })();

    tuleap.core.listFilter = function() {

        var esc_keycode = 27;
        var list_element;
        var filter_element;
        var excluded_element;

        var filterProjects = function (value) {

            $(list_element + ':not(:caseInsensitiveContains(' + value + ')):not(' + excluded_element + ')').hide();
            $(list_element +':caseInsensitiveContains(' + value + '):not(' + excluded_element +')').show();
        };

        var clearFilterProjects = function () {
            $(filter_element).val('');
            filterProjects('');
        };

        var bindClickEventOnFilter = function ($filter_element) {
            $filter_element.click(function(event) {
                event.stopPropagation();
            });
        };

        var bindKeyUpEventOnFilter = function($filter_element) {
            $filter_element.keyup(function(event) {
                if (event.keyCode === esc_keycode) {
                    clearFilterProjects();
                } else {
                    filterProjects($(this).val());
                }
            });
        };

        var init = function($filter_element, list_element_selector, excluded_element_selector) {
            filter_element   = $filter_element;
            list_element     = list_element_selector;
            excluded_element = excluded_element_selector;

            bindClickEventOnFilter($filter_element);
            bindKeyUpEventOnFilter($filter_element);
        };

        return {init: init};
    };
})(window.jQuery);
