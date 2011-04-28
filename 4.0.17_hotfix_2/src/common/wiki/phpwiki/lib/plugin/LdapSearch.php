<?php // -*-php-*- rcs_id('$Id: LdapSearch.php,v 1.3 2004/12/20 16:05:14 rurban Exp $');
/**
 Copyright 2004 John Lines

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * WikiPlugin which searches an LDAP directory.
 *
 * Note that for this version the attributes are required.
 * TODO: use the config.ini constants as defaults
 * See http://phpwiki.org/LdapSearchPlugin
 *
 * Usage Samples:
  <?plugin LdapSearch?>
  <?plugin LdapSearch
           host="localhost"
           port=389
           basedn=""
 	    filter="(cn=*)"
           attributes=""  
  ?>
  <?plugin LdapSearch host=ldap.example.com filter="(ou=web-team)" 
                      attributes="sn cn telephonenumber" ?>
  <?plugin LdapSearch host="ldap.itd.umich.edu" basedn="" filter="(sn=jensen)" attributes="cn drink" ?>
  <?plugin LdapSearch host=ldap.example.com attributes="cn sn telephonenumber" ?>
  <?plugin LdapSearch host=bugs.debian.org port=10101 basedn="dc=current,dc=bugs,dc=debian,dc=org"
                      filter="(debbugsPackage=phpwiki)" 
                      attributes="debbugsSeverity debbugsState debbugsTitle" ?>

 * @author John Lines
 */

// Constants are defined before the class.
// if (!defined('THE_END'))
//    define('THE_END', "!");

class WikiPlugin_LdapSearch
extends WikiPlugin
{
    function getName () {
        return _("LdapSearch");
    }
    function getDescription () {
        return _("Search an LDAP directory");

    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.3 $");
    }
    function getDefaultArguments() {
        return array('host' 	=> "localhost", // change to LDAP_AUTH_HOST
		     'port' 	=> 389,		// ditto
		     'basedn' 	=> "",		// LDAP_BASE_DN
                     'filter'   => "(cn=*)",
		     'attributes' => "");
    }

    // I ought to require the ldap extension, but fail sanely, if I cant get it.
    // - however at the moment this seems to work as is
    function run($dbi, $argstr, $request) {
        extract($this->getArgs($argstr, $request));

	$html = HTML::table(array('cellpadding' => 1,'cellspacing' => 1, 'border' => 1));
	$connect = ldap_connect($host, $port);
	if (!ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            $this->error(_("Failed to set LDAP protocol version to 3"));
        }
	$bind = ldap_bind($connect);
	$attr_array = array("");		// for now - 
	if (!$attributes) {
            $res = ldap_search($connect, $basedn, $filter);
        } else {
            $attr_array = split (" ",$attributes);
            $res = ldap_search($connect, $basedn, $filter,$attr_array);
        }
	$entries = ldap_get_entries($connect, $res);
 
        // If we were given attributes then we return them in the order given
        if ( $attributes ) {
            for ($i=0; $i < count($attr_array) ; $i++) { $attrcols[$i] = 0; }
            // Work out how many columns we need for each attribute.
            for ($i = 0; $i < $entries["count"]; $i++) {
                for ($ii=0; $ii<$entries[$i]["count"]; $ii++){
                    $data = $entries[$i][$ii];
                    $datalen = $entries[$i][$data]["count"];
                    if ($attrcols[$ii] < $datalen ) {
                        $attrcols[$ii] = $datalen;
                    }
                }
            }

            // Now print the headers
            $row = HTML::tr(); 
            for ($i=0; $i < count($attr_array) ; $i++) {
                $row->pushContent(HTML::th(array('colspan' => $attrcols[$i]), $attr_array[$i]));
            }
            $html->pushContent($row);
            for ($i = 0; $i<$entries["count"]; $i++) {
                // start a new row for every data value.
                $row = HTML::tr(); $nc=0;
                for ($ii=0; $ii < $entries[$i]["count"]; $ii++){
                    $data = $entries[$i][$ii];
                    // 3 possible cases for the values of each attribute.
                    switch ($entries[$i][$data]["count"]) {
                    case 0:
                        $row->pushContent(HTML::td("")); $nc++;
                        break;
                    default:
                        for ($iii=0; $iii < $entries[$i][$data]["count"]; $iii++) {
                            $row->pushContent(HTML::td($entries[$i][$data][$iii])); $nc++;
                        }
                    }
                    // Make up some blank cells if required to pad this row
                    for ( $j=0 ; $j < ($attrcols[$ii] - $nc); $j++ ) {
                        $row->pushContent(HTML::td(""));
                    }
                }
                $html->pushContent($row);
            }
        } else {
            // $i = entries
            // $ii = attributes for entry
            // $iii = values per attribute
            for ($i = 0; $i < $entries["count"]; $i++) {
                $row = HTML::tr();
                for ($ii=0; $ii < $entries[$i]["count"]; $ii++){
                    $data = $entries[$i][$ii];
                    for ($iii=0; $iii < $entries[$i][$data]["count"]; $iii++) {
                        //echo $data.":&nbsp;&nbsp;".$entries[$i][$data][$iii]."<br>";
                        if ( ! $attributes ) {
                            $row->pushContent(HTML::td($data));
                        }
                        $row->pushContent(HTML::td($entries[$i][$data][$iii]));
                    }
                }         	
                $html->pushContent($row);
            }
        }
        
        // THE_END); // ??
        return $html;
    }
};

// $Log: LdapSearch.php,v $
// Revision 1.3  2004/12/20 16:05:14  rurban
// gettext msg unification
//
// Revision 1.2  2004/10/04 23:39:34  rurban
// just aesthetics
//
// Revision 1.1  2004/09/23 12:28:12  rurban
// initial checkin from http://phpwiki.org/LdapSearchPlugin
//   by John Lines
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>