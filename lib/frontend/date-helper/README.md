# date-helper

Helps format dates according to user locale, timezone and requested date/time format.

### `IntlFormatter`

Builds a formatter configured for the given user locale, user timezone and date/time format type.

User locale should be given in "Tuleap PHP" format (with underscore separator `_`, NOT dash `-`).
Use `getLocaleWithDefault()` function to retrieve it from the document body on `data-user-locale` attribute (or default to "en_US" if missing).

Use `getTimezoneOrThrow()` function to retrieve it from the document body on `data-user-timezone` attribute (or throw an error if missing).

Date/time format type is either `"date-with-time"`, `"date"` or `"short-month"`. The choice depends on your needs.

```typescript
import { getLocaleWithDefault, getTimezoneOrThrow, IntlFormatter } from "@tuleap/date-helper";

let formatter = IntlFormatter(getLocaleWithDefault(document), getTimezoneOrThrow(document), "date");
// Read the "data-user-locale" and "data-user-timezone" attributes from document body to prepare the formatter.

formatter = IntlFormatter("en_US", "America/New_York", "date-with-time");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "2024-06-17 10:56"

formatter = IntlFormatter("en_US", "America/New_York", "date");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "2024-06-17"

formatter = IntlFormatter("en_US", "America/New_York", "short-month");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "Jun 17, 2024"

formatter = IntlFormatter("fr_FR", "Europe/Paris", "date-with-time");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "17/06/2024 16:56"

formatter = IntlFormatter("fr_FR", "Europe/Paris", "date");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "17/06/2024"

formatter = IntlFormatter("fr_FR", "Europe/Paris", "short-month");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "17 juin 2024"

formatter.format("");
formatter.format(null);
formatter.format(undefined);
// Returns empty string ""

// Only "en_US" and "fr_FR" locales are supported explicitly.
// With other locales, it defaults to "Pseudo-ISO" format for "date" and "date-with-time".
formatter = IntlFormatter("ko_KR", "Asia/Seoul", "date-with-time");
formatter.format("2024-06-17T16:56:05+02:00");
// Returns "2024-06-17 23:56"
```

### `formatDateYearMonthDay`

⚠️ DEPRECATED. This function uses the timezone of the browser (i.e. the client computer), which might be different from the configured user timezone in Tuleap. Consider using `IntlFormatter` with `"short-month"` type instead.

This function formats the given date string to the short-month type, according to user locale. User locale should be given in BCP47 format (with dash `-` separator, NOT underscore `_`).

```typescript
import { formatDateYearMonthDay } from "@tuleap/date-helper";

formatDateYearMonthDay("en-US", "2030-12-17T17:10:53Z");
// Returns "Dec 17, 2030"

formatDateYearMonthDay("fr-FR", "2021-06-25T12:33:48+01:00");
// Returns "25 juin 2021"

formatDateYearMonthDay("en-US", "");
formatDateYearMonthDay("en-US", null);
// Returns empty string ""
```

### `formatFromPhpToMoment`

⚠️ DEPRECATED. [Moment.js][0] project is end-of-life and there are native platform alternatives shipped in our supported browsers now. Consider using `IntlFormatter` instead.

This function turns the Tuleap PHP date format string to the corresponding format for Moment.js.

[0]: https://momentjs.com/
