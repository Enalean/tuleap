<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require "pre.php";
require "$DOCUMENT_ROOT/bugs/bug_utils.php";
require "$DOCUMENT_ROOT/bugs/bug_data.php";

// Initialize the global data structure before anyhting else
bug_init($group_id);
$field = $helpid;

// get the SQL field type
$res_type = db_query("SHOW COLUMNS FROM bug LIKE '$field'");

if (db_numrows($res_type)<1) {
	print "No such bug field: $field";
	exit;
}
$sql_type = db_result($res_type,0,'Type');

// Adjust field type and set help msg according to field type
if (bug_data_is_date_field($field)) {

    $fld_type = 'Date';
    $help = 'A date criteria follows the following pattern: YYYY-MM-DD
where YYYY is the year number, MM is the month number and DD is the day number. 
<p>Examples: 1999-03-21 is March 21st, 1999, 2002-12-05 is Dec 5th, 2002.
<p> ';

} else if ( preg_match('/int/i',$sql_type) ) {

    if (bug_data_is_select_box($field)) {
	$fld_type = 'Select Box';
	$help = 'A select box field can take its value in a set of predefined values.
If you are using the simple search interface only one value can be selected at a time. 
If you want to select multiple values at once use the <em>Advanced Search</em> facility.
<P> There might be 2 specific values in the list of choices: <em>\'Any\'</em> matches
any value in the list and <em>\'None\'</em> matches the items where no value has
been assigned yet';
    } else {
	$fld_type = 'Integer';
	$help = 'An integer field can take positive or (possibly) negative values and has no decimal part. 
<p>Examples: 0, 1, +2, -100..
<p>There are several ways to query an integer field. Here are the values you can specify
in a integer query field:
<ul>
<li> <b>Single Integer</b>: if you type a single integer the field will be matched against this value (e.g. 610)
<li> <b>Inequality</b>: if you use &gt;, &lt;, &gt;= or &lt;= followed by an integer
the search will look for integer values which are greater, lesser, greater or equal, lesser
or equal to the integer value (e.g. >120 , <= -30)
<li><b>Range</b>: if you use the \'integer1-integer2\' notation the search engine will
look for all values greater or equal to integer1 and lesser or equal to integer2 
(e.g. 800 - 900 for integers between 800 and 900, -45 - 12 for integers between -45
and +12)
<li><b>Regular expression</b>: although this is not very much used you can also
specify a <a href="http://www.mysql.com/doc/P/a/Pattern_matching.html">
MySQL Extended Regular Expression</a> as a matching criteria
(e.g. /^4.*8$/ will look 
for all integer values starting with a \'4\' and ending with an \'8\' and with
any number of digits in between.
</ul>';

    }

} else if ( preg_match('/float/i',$sql_type) ) {

    $fld_type = 'Floating Point Number';
	$help = 'A Floating Point Number field can take positive or (possibly) negative values, can have a decimal part and may use the exponential notation for large values.. 
<p>Examples: 0, 1.23, -2.456, 122.45E+12
<p>There are several ways to query an floating point number field. Here are the values you can specify
in such a query field:
<ul>
<li><b>Single Float</b>: if you type a single float the field will be matched against this value (e.g. 2.35 )
<li> <b>Inequality</b>: if you use &gt;, &lt;, &gt;= or &lt;= followed by an integer
the search will look for integer values which are greater, lesser, greater or equal, lesser
or equal to the integer value (e.g. >120.3 , <= -3.3456E-2)
<li><b>Range</b>: if you use the \'float1-float2\' notation the search engine will
look for all values greater or equal to integer1 and lesser or equal to integer2 
(e.g. 1.2 - 2.3 for floats between 1.2 and 2.3 )
<li><b>Regular expression</b>: although this is not very much used you can also
specify a <a href="http://www.mysql.com/doc/P/a/Pattern_matching.html">
MySQL Extended Regular Expression</a> as a matching criteria
(e.g. /^4.*8$/ will look 
for all float  values starting with a \'4\' and ending with an \'8\' and with
any number of characters in between - including the point separator).
</ul>';

}  else if ( preg_match('/text|varchar|blob/i',$sql_type) ) {
    $fld_type = 'Text';
       $help = 'A Text field can contain any kind of text characters
<p>There are basically two ways to query an text field:
<ul>
<li><b>Keyword search</b>: you can type a series of space separated keywords that will ALL be searched for in the text field (including as subtring in words)
<li><b>Regular expression</b>: You can also
specify a <a href="http://www.mysql.com/doc/P/a/Pattern_matching.html">
 MySQL Extended Regular Expression</a> as a matching criteria
(mind the  surrounding /.../ !)
<p> Examples:
<ul>
<li> /^[Aa]ddition/ :  matches any text field starting with either \'addition\'or \'Addition\'
 <li> /foo|bar|dim/ : matches text fields containing the string \'foo\', \'bar\' or \'dim\'
</ul>
</ul>';

} else {
    $fld_type = 'Unknown';
}


help_header("Bug Search -  Selection Criteria");

print '<TABLE width="100%" cellpadding="0" cellspacing="0" border="0">'."\n";
print '<TR><TD>Field Name:</TD><TD><B>'.bug_data_get_label($field)."</B></TD>\n";
print '<TR><TD>Field Type:</TD><TD><B>'.$fld_type."</B></TD>\n";
print "</TABLE>\n"; 
print '<hr><u>Description</u>:<I>'.bug_data_get_description($field).'</I>'."\n";
print '<P><u>Help</u>:<BR>'.$help.'</I>'."\n";

help_footer();
?>
