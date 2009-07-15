<?php

/**
 * Return a link to the LDAP Directory Web site of your company for the 
 * given user.
 * 
 * @param  LDAPResult $lr    Object that contains all the LDAP details of the user
 * @param  String     $value Display value of the link
 * @return String
 */
/*function custom_build_link_to_directory($lr, $value) {
    $url = 'https://directory.company.com/index.php';
    $url .= '?filter='.urlencode($lr->getLogin());
    $url .= '&attribute=cn';
    $url .= '&entry_type=people';
    $url .= '&userDN='.urlencode($lr->getDn());

    $link = '<a href="'.$url.'">'.$value.'</a>';

    return $link;
}*/

?>
