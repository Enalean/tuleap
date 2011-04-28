<?php
//
// Codendi: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, Codendi / Codendi Team, 2001-2003. All Rights Reserved
// http://codendi.com

// HTML format to use for the output of the LDAP entry
// (use %attribute_name% to insert the value of the corresponding
// LDAP attribute. 

$my_html_ldap_format = '<td colspan="2" align="center"><hr><td>'.
       '<tr valign="top"><td>Title: </td><td><b>%title%</b></td></tr>'.
       '<tr valign="top"><td>Organization: </td><td><b>%department%</b></td></tr>'.
       '<tr valign="top"><td>Address: </td><td><b>%postaladdress%</b></td></tr>'.
       '<tr valign="top"><td> </td><td><b>%postalcode% - %co%</b></td></tr>'.
       '<tr valign="top"><td>Phone #1:</td><td><b>%telephonenumber%</b></td></tr>'.
       '<tr valign="top"><td>Phone #2:</td><td><b>%telephone-office2%</b></td></tr>'.
       '<tr valign="top"><td>Fax:</td><td><b>%facsimiletelephonenumber%</b></td></tr>';

?>
