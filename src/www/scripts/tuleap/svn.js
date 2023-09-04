/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

/* global codendi:readonly */
!(function ($) {
    function initAccessControlsVersionDisplayer() {
        var version_selector = $("#old_access_file_container select");
        var version_displayer = $("#old_access_file_container textarea");
        var group_id = $("input[name=group_id]").val();

        function updateVersionDisplayer(response) {
            $("#old_access_file_form").css("visibility", "visible");

            if (response.content === null) {
                response.content = codendi.locales.svn_accessfile_history.empty;
                version_displayer.addClass("empty_version");
                version_displayer.attr("disabled", "");
            } else {
                version_displayer.removeClass("empty_version");
                version_displayer.removeAttr("disabled");
            }

            version_displayer.text(response.content);
        }

        version_selector.change(function () {
            if (this.value === "0") {
                version_displayer.text("");
                version_displayer.attr("disabled", "");
                $("#old_access_file_form").css("visibility", "hidden");
            } else {
                $.ajax({
                    url:
                        "/svn/admin/?func=access_control_version&accessfile_history_id=" +
                        this.value +
                        "&group_id=" +
                        group_id,
                    dataType: "json",
                }).success(updateVersionDisplayer);
            }
        });
    }

    function initImmutableTags() {
        var immutable_tags_tree_empty_state,
            direct_access_to_node = {},
            immutable_tags_tree = $("#immutable-tags-tree");

        if (
            immutable_tags_tree.length === 0 ||
            immutable_tags_tree.data("existing-tree").length === 0
        ) {
            return;
        }

        displayExistingTree();
        $("#immutable-tags-path")
            .on("input propertychange keyup", updateImmutablePreviewOnTheFly)
            .map(updateImmutablePreviewOnTheFly);
        $("#immutable-tags-whitelist")
            .on("input propertychange keyup", updateImmutablePreviewOnTheFly)
            .map(updateImmutablePreviewOnTheFly);

        function updateImmutablePreviewOnTheFly() {
            var immutable_tags = $("#immutable-tags-path").val().split("\n").filter(isNotEmpty),
                whitelist_tags = $("#immutable-tags-whitelist")
                    .val()
                    .split("\n")
                    .filter(isNotEmpty);

            var immutable_tags_elements = retrieveElementsMatchingTags(immutable_tags),
                whitelist_tags_elements = retrieveElementsMatchingTags(whitelist_tags);

            resetTreeState(immutable_tags_elements, whitelist_tags_elements);
            setUpMaxLengthWarnings();
            immutable_tags_elements.forEach(function (immutable_tag_element) {
                immutable_tag_element.children("span").addClass("label label-important");
                immutable_tag_element.addClass("immutable");
                immutable_tag_element.parents(".tag").addClass("parent-of-immutable");
            });
            whitelist_tags_elements.forEach(function (whitelist_tag_element) {
                whitelist_tag_element
                    .children("span")
                    .addClass("label label-success")
                    .removeClass("label-important");
                whitelist_tag_element.addClass("whitelist");
                whitelist_tag_element.parents(".tag").addClass("parent-of-whitelist");
            });
        }

        function setUpMaxLengthWarnings() {
            const immutable_tags = document.getElementById("immutable-tags-path");
            const immutable_tag_warning = document.getElementById("immutable-tag-to-big-warning");
            toggleMaxLengthWarning(immutable_tag_warning, immutable_tags);

            const whitelist_tags = document.getElementById("immutable-tags-whitelist");
            const whitelist_warning = document.getElementById("whitelist-tag-to-big-warning");
            toggleMaxLengthWarning(whitelist_warning, whitelist_tags);
        }

        function toggleMaxLengthWarning(warning, element) {
            if (warning && element && element.value.length >= element.getAttribute("maxlength")) {
                warning.removeAttribute("hidden");
            } else {
                warning.setAttribute("hidden", "");
            }
        }

        function retrieveElementsMatchingTags(tags) {
            var regex = new RegExp(
                "^(" +
                    tags.reduce(function (regex, path) {
                        if (regex) {
                            regex += "|";
                        }

                        path = path.replace(/\*/g, "[^/]+");

                        return regex + path + "|" + path + "/";
                    }, "") +
                    ")$",
            );

            var tags_elements = Object.keys(direct_access_to_node).reduce(function (
                tags_elements,
                path,
            ) {
                if (path.match(regex)) {
                    tags_elements.push(direct_access_to_node[path].element);
                }

                return tags_elements;
            }, []);

            return tags_elements;
        }

        function showExampleEmptyState() {
            $(".immutable-tags-examples").addClass("empty");
        }

        function hideExampleEmptyState() {
            $(".immutable-tags-examples").removeClass("empty");
        }

        function showTreeEmptyState() {
            if (!immutable_tags_tree_empty_state) {
                immutable_tags_tree_empty_state = $("<div>")
                    .addClass("empty-state")
                    .text($("#immutable-tags-tree").data("empty-state-text"));

                immutable_tags_tree.prepend(immutable_tags_tree_empty_state);
            }

            immutable_tags_tree_empty_state.show();
            immutable_tags_tree_empty_state.nextAll().hide();
        }

        function hideTreeEmptyState() {
            if (immutable_tags_tree_empty_state) {
                immutable_tags_tree_empty_state.hide();
                immutable_tags_tree_empty_state.nextAll().show();
            }
        }

        function resetTreeState(immutable_tags_elements, whitelist_tags_elements) {
            var classnames = [
                "label",
                "label-important",
                "label-success",
                "immutable",
                "whitelist",
                "parent-of-immutable",
                "parent-of-whitelist",
                "active",
            ];

            if (immutable_tags_elements.length === 0 && whitelist_tags_elements.length === 0) {
                showTreeEmptyState();
            } else {
                hideTreeEmptyState();
            }
            showExampleEmptyState();

            immutable_tags_tree
                .find(classnames.reduce(convertClassNameToSelector, []).join(", "))
                .removeClass(classnames.join(" "));
        }

        function convertClassNameToSelector(selectors, classname) {
            selectors.push("." + classname);

            return selectors;
        }

        function isNotEmpty(value) {
            return value;
        }

        function displayExistingTree() {
            var root = convertFlatTreeIntoTreeNode();

            immutable_tags_tree.text("");
            displayTreeNode(immutable_tags_tree, root);
        }

        function displayTreeNode(element, root) {
            var icon_folder = '<i class="far fa-folder-open"></i> ',
                children_element = $("<div>").addClass("children"),
                label_element = $("<span>").attr("title", root.path).text(basename(root.path)),
                root_element = $("<div>")
                    .attr("data-path", root.path)
                    .addClass("tag")
                    .append(icon_folder, label_element)
                    .append(children_element);

            root.element = root_element;
            element.append(root_element);

            label_element.click(onLabelClick);

            root.children.forEach(function (node) {
                displayTreeNode(children_element, node);
            });

            children_element.append($("<div>").html(icon_folder + "..."));
        }

        function onLabelClick() {
            if (!$(this).hasClass("label")) {
                return;
            }

            $(".tag > .label").removeClass("active");
            $("#immutable-tags-console").removeClass("whitelist-not-in-immutable");
            $(this).addClass("active");

            $("#tag-name-example")
                .removeClass("label-important label-success")
                .addClass($(this).hasClass("label-important") ? "label-important" : "label-success")
                .text(basename($(this).attr("title")));

            $(".tag-path-example").text($(this).attr("title").replace(/\/$/, ""));

            if ($(this).hasClass("label-success") && $(this).parents(".immutable").length === 0) {
                $("#immutable-tags-console").addClass("whitelist-not-in-immutable");
            }

            hideExampleEmptyState();
        }

        function convertFlatTreeIntoTreeNode() {
            var root;

            immutable_tags_tree.data("existing-tree").forEach(function (path) {
                var node = {
                    path: path,
                    children: [],
                };

                if (!root) {
                    root = node;
                }

                if (direct_access_to_node[dirname(path)]) {
                    direct_access_to_node[dirname(path)].children.push(node);
                }

                direct_access_to_node[path] = node;
            });

            return root;
        }

        function dirname(path) {
            return path.replace(/\/[^/]+\/$/, "/");
        }

        function basename(path) {
            return path.replace(/^.*\/([^/]+)\/$/, "$1");
        }
    }

    $(document).ready(function () {
        initAccessControlsVersionDisplayer();
        initImmutableTags();
    });
})(window.jQuery);
