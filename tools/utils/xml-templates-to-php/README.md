XML Templates to PHP
====================

This tools helps to convert a XML template to corresponding PHP code.

To convert one tracker
----------------------

```
composer install
php index.php convert-tracker --src /path/to/xml --tracker-id T448
```

This will convert the tracker XML node with `id="T448"` parameter to PHP code. Please adjust `T448` to your context.

This can then be copy-pasted into Tuleap project template generation code (See IssuesTemplate for example).

**Note:** If part of the generated code depends on various plugins (a warning will be displayed in the
console if it is the case), then it will have to be manually separated.
