/**
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/* global Ajax:readonly $:readonly */

function tracker_quote_comment(who, commentId) {
    var textarea = $("tracker_artifact_comment");
    var element = $("comment_" + commentId + "_content");
    if (textarea && element) {
        if ($("comment_format_html").selected) {
            // Get current query parameters
            //eslint-disable-next-line no-new-wrappers
            var qs = new String(document.location);
            var queryParams = qs.toQueryParams();

            // Build Ajax request
            var url = "?func=getcomment";
            url += "&aid=" + queryParams["aid"];
            url += "&artifact_history_id=" + commentId;
            new Ajax.Request(url, {
                onSuccess: function (response) {
                    textarea.value += who + ":\n";
                    textarea.value +=
                        "<blockquote>\n" + response.responseText + "\n</blockquote>\n";
                },
            });
        } else {
            var str = element.textContent ? element.textContent : element.innerText;
            if (
                textarea.value.length >= 1 &&
                textarea.value.substring(textarea.value.length - 1) != "\n"
            ) {
                textarea.value += "\n";
            }
            if (
                textarea.value.length >= 1 &&
                textarea.value.substring(textarea.value.length - 2, textarea.value.length - 1) !=
                    "\n"
            ) {
                textarea.value += "\n";
            }

            textarea.value += who + ":\n> ";
            textarea.value += str.replace(/\\n/gi, "\n> ");
            textarea.value += "\n";
        }
        textarea.scrollTop = textarea.scrollHeight;
    }
}
window.tracker_quote_comment = tracker_quote_comment;
