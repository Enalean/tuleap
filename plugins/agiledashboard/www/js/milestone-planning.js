/* 
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

(function($) {
        $(function() {
            var view = {
                url: '#',
                id: 123,
                title: 'bla',
                remaining_effort: 12,
                parent_title: 'Epic ploc',
                parent_url: '#'
            };
            var template = '<tr><td><a href="{{url}}">{{id}}/a></td><td>{{title}}</td><td>{{remaining_effort}}</td><td><a href="{{#parent_url}}">{{parent}}</a></td></tr>';

            $("#accordion > div").accordion({
                header: "h4",
                collapsible: true,
                active: false,
                beforeActivate: function (event, ui) {
                    //$('#bla').append(Mustache.render(template, view));
                }
            });
        })
    })(jQuery);
