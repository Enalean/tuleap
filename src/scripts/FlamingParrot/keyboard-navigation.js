/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

import $ from "jquery";
import key from "keymaster";
import "keymaster-sequence";

function deployKeyboardShortcuts() {
    var my = $("#navbar-my"),
        logo = $("#navbar-logo"),
        project = $("#navbar-project"),
        project_dropdown = $("#dropdown-project"),
        user_navigation = $("#navbar-user-navigation"),
        user_navigation_dropdown = $("#dropdown-user-navigation"),
        sidebar_collapse = $("#sidebar-collapser"),
        sidebar_git = $("a#sidebar-plugin_git"),
        sidebar_project_home = $("a#sidebar-homepage"),
        sidebar_wiki = $("a#sidebar-wiki"),
        sidebar_svn = $("a#sidebar-svn"),
        sidebar_tracker = $("a#sidebar-plugin_tracker"),
        sidebar_dashboard = $("a#sidebar-dashboard"),
        sidebar_agiledashboard = $("a#sidebar-plugin_agiledashboard"),
        sidebar_docman = $("a#sidebar-docman"),
        sidebar_cvs = $("a#sidebar-cvs"),
        sidebar_file = $("a#sidebar-file"),
        sidebar_mediawiki = $("a#sidebar-plugin_mediawiki"),
        sidebar_forum = $("a#sidebar-forum"),
        sidebar_mail = $("a#sidebar-mail"),
        sidebar_news = $("a#sidebar-news");

    setShortcutOnLink(["g", "m"], my);
    setShortcutOnLink(["g", "h"], logo);
    setShortcutOnProjectDropDown(["g", "p"], project, project_dropdown);
    setShortcutOnUserDropDown(["g", "a"], user_navigation, user_navigation_dropdown);
    setShortcutOnSidebar("s", sidebar_collapse);
    setShortcutExitInput(27);
    setHelpModalShortcut(63);
    setShortcutOnSidebarLink(["p", "h"], sidebar_dashboard);
    setShortcutOnSidebarLink(["p", "a"], sidebar_agiledashboard);
    setShortcutOnSidebarLink(["p", "p"], sidebar_project_home);
    setShortcutOnSidebarLink(["p", "d"], sidebar_docman);
    setShortcutOnSidebarLink(["p", "m"], sidebar_mediawiki);
    setShortcutOnSidebarLink(["p", "w"], sidebar_wiki);
    setShortcutOnSidebarLink(["p", "t"], sidebar_tracker);
    setShortcutOnSidebarLink(["p", "g"], sidebar_git);
    setShortcutOnSidebarLink(["p", "s"], sidebar_svn);
    setShortcutOnSidebarLink(["p", "c"], sidebar_cvs);
    setShortcutOnSidebarLink(["p", "f"], sidebar_file);
    setShortcutOnSidebarLink(["p", "b"], sidebar_forum);
    setShortcutOnSidebarLink(["p", "n"], sidebar_news);
    setShortcutOnSidebarLink(["p", "l"], sidebar_mail);
}

function setShortcutOnProjectDropDown(shortCut, element, dropdown) {
    key.sequence(shortCut, function () {
        if (element.length > 0) {
            element.click();
            if (dropdown !== undefined) {
                dropdown.find("input").first().focus();
                return false;
            }
        }
    });
}

function setShortcutOnUserDropDown(shortCut, element, dropdown) {
    key.sequence(shortCut, function () {
        if (element.length > 0) {
            element.click();
            if (dropdown !== undefined) {
                dropdown.find("a")[1].focus();
                return false;
            }
        }
    });
}

function setShortcutOnSidebar(shortCut, element) {
    key(shortCut, function () {
        if (element.length > 0) {
            element.click();
            return false;
        }
    });
}

function setShortcutOnSidebarLink(shortCut, element) {
    key.sequence(shortCut, function () {
        if (element.length > 0) {
            window.location.href = element.attr("href");
            return false;
        }
    });
}

function setShortcutOnLink(shortCut, element) {
    key.sequence(shortCut, function () {
        if (element.length > 0) {
            window.location.href = element.attr("href");
            return false;
        }
    });
}

function showOrHideHelpModal(help_modal) {
    help_modal.modal("toggle");
}

function setShortcutExitInput(shortCut) {
    $(document).keyup(function (event) {
        if (event.which === shortCut) {
            var help_modal = $("#keyboard-navigation-help-modal");
            if (help_modal.hasClass("in")) {
                showOrHideHelpModal(help_modal);
                return false;
            } else if ($(document.activeElement).is("input")) {
                $(document.activeElement).blur();
            }
            $("body").click();
            return false;
        }
    });
}

function setHelpModalShortcut(shortCut) {
    var help_modal = $("#keyboard-navigation-help-modal");
    $(document).keypress(function (event) {
        if (event.which === shortCut && !$(document.activeElement).is("textarea, input")) {
            showOrHideHelpModal(help_modal);
        }
    });
}

$(document).ready(deployKeyboardShortcuts);
