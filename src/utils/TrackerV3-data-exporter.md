Tracker v3 export known issues
==============================

General
-------

Fields might not have history or, worse, can have partial history (changes recorded
only for a portion of artifact lifetime).
In those cases, a fake changeset is created at the time of export for those values.

Attachment
----------

* Deleted attachments are not exported.
  They will not appears in the history either.

* If an artifact contains 2 attachments with the same name, export will not
  be able to distinguish them and it will skip them.

Numeric fields
--------------

Values of Integer (resp. Float) fields are exported as int (resp. int). It
sounds obvious but as you may know by now the tracker v5 fields like Integer or
Float cannot change their type whereas it was the case in v3. This means that
in the history of an Integer (Float) field in v3 we may find values that are
plain string instead of int (float) if the field type had been changed from
String to Integer (float). The values are then cast into the right type in
order to be imported into a tracker v5.
