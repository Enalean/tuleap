/* global
    $:readonly
    Ajax:readonly
    $H:readonly
    Builder:readonly
    codendi:readonly

    processor_id:readonly
    processor_name:readonly
    type_id:readonly
    type_name:readonly
    group_id:readonly
    choose:readonly
    browse:readonly
    local_file:readonly
    scp_ftp_files:readonly
    upload_text:readonly
    add_file_text:readonly
    add_change_log_text:readonly
    view_change_text:readonly
    release_mode:readonly

    default_permissions_text:writable
    ugroups_name:readonly
*/

function replace(expr, a, b) {
    var i = 0;
    while (i != -1) {
        i = expr.indexOf(a, i);
        if (i >= 0) {
            expr = expr.substring(0, i) + b + expr.substring(i + a.length);
            i += b.length;
        }
    }
    return expr;
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function update_news() {
    var rel_name = $("release_name");
    var subject = $("release_news_subject");
    var details = $("release_news_details");

    if (subject === null || details === null) {
        return;
    }

    var a = this.relname;
    var b = rel_name.value;
    var expr1 = subject.value;
    var expr2 = details.value;

    var new_subject = replace(expr1, a, b);
    var new_details = replace(expr2, a, b);
    subject.value = new_subject;
    details.value = new_details;
}

//variables
var nb_rows = 1;
var nb_files = 0;
var used_ftp_files = [];
var available_ftp_files = [];
var selects = {};
var current_select_number = 0;

function build_select_file(number) {
    /**/
    while (selects[number].childNodes.length) {
        selects[number].removeChild(selects[number].lastChild);
    }
    /**/

    //remove all ftp files aldready selected in the avalaible ftp file list (result in the non_used_ftp_files)
    var non_used_ftp_files = available_ftp_files;
    used_ftp_files.each(function (num) {
        non_used_ftp_files = non_used_ftp_files.without(num);
    });

    //for each non used ftp files, add a corresponding option ligne (used in the select files)
    var builder_node_files = [];
    non_used_ftp_files.each(function (num) {
        if (num != "") {
            builder_node_files.push(Builder.node("option", { value: num }, num));
        }
    });
    var opts = [
        Builder.node("option", { value: "-1" }, choose),
        Builder.node("optgroup", { label: local_file }, [
            Builder.node("option", { value: "-2" }, browse),
        ]),
        Builder.node("optgroup", { label: scp_ftp_files }, builder_node_files),
    ];
    opts.each(function (opt) {
        selects[number].appendChild(opt);
    });
    selects[number].options[0].selected = "selected";
}

//function to add a new file by http or ftp/scp mode
function add_new_file() {
    nb_files++;
    var id = nb_rows++;

    var builder_node_processor = [];
    var builder_node_type = [];

    //TR tag construction
    var row = Builder.node("tr", { id: "row_" + id });

    //TD tag constuction, add the trash image this tag (used to remove the line)
    var cell_trash = Builder.node("td");
    var image = Builder.node("img", {
        src: "./../images/delete.png",
        onclick: "delete_file('row_" + id + "'," + id + ")",
        style: "cursor:pointer",
    });

    row.appendChild(cell_trash);

    //TD tag constuction, add the select file boxe to this tag (used to choose the file)
    var cell = Builder.node("td", { id: "td_file_" + id });
    var select = Builder.node("select", { name: "ftp_file_list" });
    selects[current_select_number] = select;
    build_select_file(current_select_number);

    //add the onchange event on the select boxe
    Event.observe(
        select,
        "change",
        function (evt, current_select_number, id, cell_trash, image) {
            onselectchange(this, current_select_number, id, cell_trash, image);
        }.bindAsEventListener(select, current_select_number, id, cell_trash, image),
        true,
    );
    cell.appendChild(select);

    //Browse file field creation
    var file = Builder.node("input", { type: "file", id: "file_" + id, name: "file[]" });
    Element.hide(file);
    cell.appendChild(file);

    //ftp file field creation
    var ftp_file = Builder.node("input", {
        type: "hidden",
        id: "ftp_file_" + id,
        name: "ftp_file[]",
    });
    Element.hide(ftp_file);
    cell.appendChild(ftp_file);

    row.appendChild(cell);

    //for each processor, add a corresponding option ligne
    builder_node_processor.push(Builder.node("option", { value: "100" }, choose));
    processor_id.each(function (id, item) {
        builder_node_processor.push(Builder.node("option", { value: id }, processor_name[item]));
    });

    //TD tag constuction, add the select processor type boxe to this tag (used to choose the processor)
    cell = Builder.node("td");
    select = Builder.node("select", { id: "processor_" + id }, builder_node_processor);
    select.options[0].selected = "selected";
    cell.appendChild(select);
    row.appendChild(cell);

    //for each type, add a corresponding option ligne
    builder_node_type.push(Builder.node("option", { value: "100" }, choose));
    type_id.each(function (id, item) {
        builder_node_type.push(Builder.node("option", { value: id }, type_name[item]));
    });

    //TD tag constuction, add the select file type boxe to this tag (used to choose the type)
    cell = Builder.node("td");
    select = Builder.node("select", { id: "type_" + id }, builder_node_type);
    select.options[0].selected = "selected";
    cell.appendChild(select);
    row.appendChild(cell);

    //Add field for reference checksum
    cell = Builder.node("td");
    var md5sum = Builder.node("input", {
        type: "text",
        id: "reference_md5_" + id,
        size: 36,
        name: "reference_md5",
    });
    cell.appendChild(md5sum);
    row.appendChild(cell);

    cell = Builder.node("td");
    var comment_cell = Builder.node("textarea", {
        id: "comment_" + id,
        cols: 20,
        rows: 1,
        name: "comment",
    });
    cell.appendChild(comment_cell);
    row.appendChild(cell);

    $("files_body").appendChild(row);
    current_select_number++;
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function add_new_link(event) {
    event.preventDefault();
    var table = document.getElementById("frs-uploaded-link-creation");

    table.removeClassName("frs-uploaded-link-creation");

    var row = table.insertRow(-1);
    var last_insert_row_id = table.getElementsByTagName("tr").length;

    var cell_name = row.insertCell(0);
    var cell_link = row.insertCell(1);

    // eslint-disable-next-line no-unsanitized/property
    cell_name.innerHTML =
        '<input type="text"' +
        ' name="uploaded-link-name[' +
        last_insert_row_id +
        ']" ' +
        ' id="uploaded-link-name[' +
        last_insert_row_id +
        ']" ' +
        "/>";

    // eslint-disable-next-line no-unsanitized/property
    cell_link.innerHTML =
        '<input type="text" ' +
        'name="uploaded-link[' +
        last_insert_row_id +
        ']" ' +
        'id="uploaded-link[' +
        last_insert_row_id +
        ']" ' +
        'size="60"' +
        'placeholder="https://example.com"' +
        'pattern="(https?|ftps?)://.+"' +
        "required " +
        "/>";
}

function onselectchange(select, number, id, cell_trash, image) {
    var h = {};
    if (select.options[select.selectedIndex].value == "-2") {
        $H(selects).each(function (pair) {
            if (pair.key != number) {
                h[pair.key] = pair.value;
            }
        });
        selects = h;
        //the http mode was selected
        Element.show("file_" + id);
        //Element.hide(select);
        select.remove();
        document.getElementById("ftp_file_" + id)?.remove();
        $("processor_" + id).name = "file_processor[]";
        $("type_" + id).name = "file_type[]";
        $("reference_md5_" + id).name = "reference_md5[]";
        $("comment_" + id).name = "comment[]";
        cell_trash.appendChild(image);
    } else if (select.options[select.selectedIndex].value != "-1") {
        $H(selects).each(function (pair) {
            if (pair.key != number) {
                h[pair.key] = pair.value;
            }
        });
        selects = h;
        //the ftp/scp move was selected, wa change the select box to a readonly text field
        //we add the file to the used_ftp_files list
        //Element.hide(select);
        select.remove();
        document.getElementById("file_" + id)?.remove();
        Element.show("ftp_file_" + id);
        $("ftp_file_" + id).value = select.options[select.selectedIndex].value;
        used_ftp_files.push(select.options[select.selectedIndex].value);
        $("processor_" + id).name = "ftp_file_processor[]";
        $("type_" + id).name = "ftp_file_type[]";
        $("reference_md5_" + id).name = "ftp_reference_md5[]";
        cell_trash.appendChild(image);
        $("td_file_" + id).firstChild.insertAdjacentText(
            "beforebegin",
            select.options[select.selectedIndex].value,
        );

        //remove entry from other select boxes
        $H(selects)
            .keys()
            .each(function (number) {
                build_select_file(number);
            });
    }
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function delete_file(row_id, id) {
    nb_files--;
    if ($("file_" + id) == null) {
        // we remove the file from the used ftp files list
        used_ftp_files = used_ftp_files.without($("ftp_file_" + id).value);
        $H(selects)
            .keys()
            .each(function (number) {
                build_select_file(number);
            });
    }
    document.getElementById(row_id)?.remove();
    if (
        (release_mode == "creation" && nb_files == 0) ||
        (release_mode == "edition" && $("nb_files").value == 0 && nb_files == 0)
    ) {
        add_new_file();
    }
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function show_upload_change_log() {
    Element.hide("cl_upload_link");
    Element.hide("change_log_area");
    Element.show("upload_change_log");
    Element.show("cancel_change_log");
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function cancel_update_change_log() {
    Element.show("change_log_area");
    Element.show("cl_upload_link");
    //remove the file input and add it just after to set the file value to "" otherwise the file will be upload when saving the release
    document.getElementById("upload_change_log")?.remove();
    Element.insert("change_log_title", {
        after: '<TR id="upload_change_log"><TD><input type="file" id="uploaded_change_log" name="uploaded_change_log"  size="30"></TD></TR>',
    });
    Element.hide("upload_change_log");
    Element.insert("uploaded_change_log", {
        after: '<input type="button" id="cancel_change_log" name="cancel_change_log"  size="30" value="cancel" onclick="cancel_update_change_log(); return false;">',
    });
    Element.hide("cancel_change_log");
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function add_change_log() {
    Element.hide("add_change_log");
    Element.show("change_log_title");
    Element.show("change_log_area");
    Element.insert("change_log", {
        after:
            '<a id="cl_upload_link" href="#upload_change_log" onclick="show_upload_change_log(); return false;">' +
            upload_text +
            "</a>",
    });
    Element.insert("uploaded_change_log", {
        after: '<input type="button" id="cancel_change_log" name="cancel_change_log"  size="30" value="cancel" onclick="cancel_update_change_log(); return false;">',
    });
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function show_upload_notes() {
    Element.show("upload_notes");
    Element.hide("rn_upload_link");
    Element.insert("uploaded_notes", {
        after: '<input type="button" id="cancel_notes" name="cancel_notes"  size="30" value="cancel" onclick="cancel_update_notes(); return false;">',
    });
    Element.hide("release_notes_area");
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function cancel_update_notes() {
    Element.show("release_notes_area");
    Element.show("rn_upload_link");
    //remove the file input and add it just after to set the file value to "" otherwise the file will be upload when saving the release
    document.getElementById("upload_notes")?.parentNode?.removeChild();
    Element.insert("notes_title", {
        after: '<TR id="upload_notes"><TD><input id="uploaded_notes" type="file" name="uploaded_release_notes"  size="30"></TD></TR>',
    });
    Element.hide("upload_notes");
    Element.insert("uploaded_notes", {
        after: '<input type="button" id="cancel_notes" name="cancel_notes"  size="30" value="cancel" onclick="cancel_update_notes(); return false;">',
    });
    Element.hide("cancel_notes");
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function view_change_permissions() {
    Element.hide("default_permissions");
    Element.show("permissions");
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
function refresh_file_list() {
    var url = "frsajax.php?group_id=" + group_id + "&action=refresh_file_list";

    new Ajax.Request(url, {
        method: "get",
        onSuccess: function (transport) {
            //eslint-disable-next-line no-eval
            var json = eval("(" + transport.responseText + ")");
            available_ftp_files = json.msg.split(",");
            $H(selects)
                .keys()
                .each(function (number) {
                    build_select_file(number);
                });
        },
    });
}

Event.observe(window, "load", function () {
    //Add new file part
    //Element.hide('row_0');
    document.getElementById("row_0")?.remove();
    if (release_mode == "creation" || (release_mode == "edition" && $("nb_files").value == 0)) {
        add_new_file();
    }

    Element.insert("files", {
        after:
            '<a href="#add_new_file" onclick="add_new_file(); return false;">' +
            add_file_text +
            "<a>",
    });

    //Release Notes
    Element.hide("upload_notes");
    Element.insert("release_notes", {
        after:
            '<a id="rn_upload_link" href="#upload_release_notes" onclick="show_upload_notes();return false;">' +
            upload_text +
            "</a>",
    });

    //Change Log
    if (
        (release_mode == "edition" && $("text_area_change_log").value == "") ||
        release_mode == "creation"
    ) {
        Element.hide("change_log_title");
        Element.hide("upload_change_log");
        Element.hide("change_log_area");
        Element.insert("change_log_title", {
            before:
                '<TR id="add_change_log"><TD><a href="#add_change_log" onclick="add_change_log(); return false;">' +
                add_change_log_text +
                "</a></TD></TR>",
        });
    } else if (release_mode == "edition" && $("text_area_change_log").value != "") {
        Element.insert("uploaded_change_log", {
            after: '<input type="button" id="cancel_change_log" name="cancel_change_log"  size="30" value="cancel" onclick="cancel_update_change_log(); return false;">',
        });
        Element.hide("cancel_change_log");
        Element.hide("upload_change_log");
        Element.insert("change_log", {
            after:
                '<a id="cl_upload_link" href="#upload_change_log" onclick="show_upload_change_log(); return false;">' +
                upload_text +
                "</a>",
        });
    }
    //News
    if ($("submit_news") !== null) {
        Element.hide("tr_subject");
        Element.hide("tr_details");
        Element.hide("tr_public");
        Element.hide("tr_private");

        Event.observe($("submit_news"), "click", function () {
            if ($("submit_news").checked) {
                Element.show("tr_subject");
                Element.show("tr_details");
                Element.show("tr_public");
                Element.show("tr_private");
            } else {
                Element.hide("tr_subject");
                Element.hide("tr_details");
                Element.hide("tr_public");
                Element.hide("tr_private");
            }
        });
    }

    //Permissions
    if ($("package_id") != null) {
        Event.observe($("package_id"), "change", function () {
            if (release_mode == "creation") {
                new Ajax.Updater(
                    "permissions_list",
                    "frsajax.php?group_id=" +
                        group_id +
                        "&action=permissions_frs_package&package_id=" +
                        $("package_id").value,
                    { method: "get" },
                );
            }
        });
    }
    Element.hide("permissions");
    if (release_mode == "edition") {
        default_permissions_text += "<B>" + ugroups_name + "</B>";
    }
    Element.insert("permissions", {
        before:
            '<TR id="default_permissions">' +
            "<TD>" +
            default_permissions_text +
            '<a href="#change_permissions" onclick="view_change_permissions(); return false;">' +
            view_change_text +
            "</a></TD></TR>",
    });
});

document.observe("dom:loaded", function () {
    var create_release_btn = $("create_release");

    function updateFeedbackBox(html_msg) {
        $("feedback").innerHTML = html_msg; // eslint-disable-line no-unsanitized/property
        Element.scrollTo("feedback");
    }

    function checkFileUploadSize(onsuccess_callback) {
        var input_files = $("files").select("input[type=file]"),
            total_bytes = 0;

        if (!window.FileReader) {
            // Unsupported browser, continue validation
            onsuccess_callback();
            return;
        }

        if (!input_files.size()) {
            // No files to check, continue validation
            onsuccess_callback();
            return;
        }

        total_bytes = input_files.inject(0, function (total_bytes, input_file) {
            var files = input_file.files,
                length = files.length;

            // Since html5 we may have multiple files per input type=file
            for (var i = 0; i < length; ++i) {
                total_bytes += files[i].size;
            }

            return total_bytes;
        });

        if (total_bytes < $("frs_form").down("input[name=MAX_FILE_SIZE]").value) {
            onsuccess_callback();
        } else {
            updateFeedbackBox(
                '<ul class="feedback_error"><li>' +
                    codendi.locales.files.error_max_file_size +
                    "</li></ul>",
            );
        }
    }

    function checkParametersOnServer() {
        $("feedback").innerHTML = "";
        var url;
        if (release_mode == "creation") {
            var package_id;
            if ($("package_id")) {
                package_id = $("package_id").value;
            } else {
                package_id = null;
            }
            url =
                "frsajax.php?group_id=" +
                group_id +
                "&action=validator_frs_create&package_id=" +
                package_id +
                "&date=" +
                $("release_date").value +
                "&name=" +
                encodeURIComponent($("release_name").value);
        } else {
            url =
                "frsajax.php?group_id=" +
                group_id +
                "&action=validator_frs_update&package_id=" +
                $("package_id").value +
                "&date=" +
                $("release_date").value +
                "&name=" +
                encodeURIComponent($("release_name").value) +
                "&release_id=" +
                $("release_id").value;
        }
        new Ajax.Request(url, {
            method: "get",
            onSuccess: function (transport, json) {
                if (json.valid) {
                    this.form.submit();
                } else {
                    updateFeedbackBox(json.msg);
                }
            }.bind(create_release_btn),
        });
    }

    if (create_release_btn) {
        create_release_btn.observe("click", function check_parameters(evt) {
            Event.stop(evt);
            // Yay Promises!
            checkFileUploadSize(checkParametersOnServer);

            return false;
        });
    }
});
