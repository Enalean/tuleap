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
