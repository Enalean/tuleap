import angular from "angular";

import computed_field from "./computed-field/computed-field.js";
import permission_field from "./permission-field/permission-field.js";
import file_field from "./file-field/file-field.js";
import date_field from "./date-field/date-field.js";
import open_list_field from "./open-list-field/open-list-field.js";
import link_field from "./link-field/link-field.js";

export default angular.module("tuleap-artifact-modal-fields", [
    file_field,
    computed_field,
    permission_field,
    date_field,
    open_list_field,
    link_field
]).name;
