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

Multi selectboxes
-----------------

  Statics values
  ''''''''''''''

We can have some strange cases in database side. It stores:

  * A string comma separated if we select multiple values
  * The label if its a unique value
  * 0 when the field is cleared without selecting any value
  * 'Any' or 'Tous' regarding the langage when the value is saved if the old value
  *   is a cleared field

  * We can manage the first case because we are sur that there is only label
    The two following cases are ambiguous : how to be sure that 0 is the label of the value
    or the representation of a cleared field ?

  * Then, if the unique value is an int, how to be sure that this numeric is a
    label instead of an ID sometimes stored in the database ?

  * If a label has a comma in its content, we are not able to manage it.

  * Finally, when the label can be a system word, we don't know if it's the label
    or a magic system word saved in the database.

  Binded to users
  '''''''''''''''

On the database side, we have:

  * A string (user names) comma separated if we select multiple users
  * A blank value if an error is raised when the form was submitted

  * When we have an entry with a blank value, we skip it.