import { escape, clone } from "lodash-es";
import escapeStringRegexp from "escape-string-regexp";

export default InPropertiesFilter;

InPropertiesFilter.$inject = ["$filter"];

function InPropertiesFilter($filter) {
    var amCalendarFilter = $filter("amCalendar");

    const HTML_FORMAT = "html";

    return function (list, terms) {
        if (!terms || terms === "") {
            return clone(list);
        }

        var properties = ["id", "label"],
            keywords = terms.split(" "),
            filtered_list = list;

        keywords.forEach(function (keyword) {
            const regexp = new RegExp(escapeStringRegexp(keyword), "gi");
            const encoded_regexp = new RegExp(escapeStringRegexp(escape(keyword)), "gi");

            filtered_list = $filter("filter")(filtered_list, function (item) {
                if (
                    properties.some(function (property) {
                        return match(item[property]);
                    })
                ) {
                    return true;
                }

                return item.card_fields.some(function (card_field) {
                    if (!card_field) {
                        return false;
                    }

                    switch (card_field.type) {
                        case "sb":
                        case "rb":
                        case "cb":
                        case "tbl":
                        case "msb":
                        case "shared":
                            if (typeof card_field.values === "undefined") {
                                return false;
                            }

                            return card_field.values.some(function (value) {
                                if (typeof value.display_name !== "undefined") {
                                    return match(value.display_name);
                                }
                                return match(value.label);
                            });
                        case "string":
                        case "int":
                        case "float":
                        case "aid":
                        case "atid":
                        case "priority":
                            return match(card_field.value);
                        case "text":
                            return card_field.format === HTML_FORMAT
                                ? matchEncoded(card_field.value)
                                : match(card_field.value);
                        case "file":
                            return card_field.file_descriptions.some(function (file) {
                                return match(file.name);
                            });
                        case "cross":
                            return card_field.value.some(function (link) {
                                return match(link.ref);
                            });
                        case "perm":
                            return card_field.granted_groups.some(function (group) {
                                return match(group);
                            });
                        case "subby":
                        case "luby":
                            return match(card_field.value.display_name);
                        case "date":
                        case "lud":
                        case "subon":
                            return match(amCalendarFilter(card_field.value));
                        case "computed":
                            if (card_field.manual_value !== null) {
                                return match(card_field.manual_value);
                            }
                            return match(card_field.value);
                        default:
                            return false;
                    }
                });

                function match(value) {
                    return String(value).match(regexp);
                }

                function matchEncoded(value) {
                    return String(value).match(encoded_regexp);
                }
            });
        });

        return filtered_list;
    };
}
