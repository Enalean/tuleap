/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

var tuleap = tuleap || {};

// Search for a class in loaded stylesheets
tuleap.getStyleClass = function (className) {
    var s, r;
    var re = new RegExp("\\." + className + "$", "gi");
    if (document.all) {
        for (s = 0; s < document.styleSheets.length; s++) {
            for (r = 0; r < document.styleSheets[s].rules.length; r++) {
                if (
                    document.styleSheets[s].rules[r].selectorText &&
                    document.styleSheets[s].rules[r].selectorText.search(re) != -1
                ) {
                    return document.styleSheets[s].rules[r].style;
                }
            }
        }
    } else if (document.getElementById) {
        for (s = 0; s < document.styleSheets.length; s++) {
            for (r = 0; r < document.styleSheets[s].cssRules.length; r++) {
                if (
                    document.styleSheets[s].cssRules[r].selectorText &&
                    document.styleSheets[s].cssRules[r].selectorText.search(re) != -1
                ) {
                    document.styleSheets[s].cssRules[r].sheetIndex = s;
                    document.styleSheets[s].cssRules[r].ruleIndex = s;
                    return document.styleSheets[s].cssRules[r].style;
                }
            }
        }
    } else if (document.layers) {
        return document.classes[className].all;
    }
    return null;
};

// Search for a property for a class in loaded stylesheets
tuleap.getStyleClassProperty = function (className, propertyName) {
    var styleClass = tuleap.getStyleClass(className);
    if (styleClass) {
        return styleClass[propertyName];
    }
    return null;
};
