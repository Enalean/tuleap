<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

header("Cache-Control: no-cache, no-store, must-revalidate");

require __DIR__ . '/../include/pre.php';

$hp = Codendi_HTMLPurifier::instance();

$vPv = new Valid_Pv();
if ($request->valid($vPv) && $request->get('pv') == 2) {
    $pv = 2;
    $HTML->pv_header(array());
} else {
    $pv = 0;
    site_header(array('title' => $Language->getText('my_redirect', 'page_title')));
}

$vReturnTo = new Valid_String('return_to');
$vReturnTo->required();
if ($request->valid($vReturnTo)) {
    // Re-serialize feedback to display it on the 'return_to' page.
    $HTML->_serializeFeedback();

    $url_verifier = new URLVerification();
    $return_url = '/';
    if ($url_verifier->isInternal($request->get('return_to'))) {
        $return_url = $request->get('return_to');
    }

    $redirect = $Language->getText('my_redirect', 'return_to', array($hp->purify($return_url, CODENDI_PURIFIER_CONVERT_HTML)));

    print '
<script type="text/javascript">
function return_to_url() {
  window.location="' . $hp->purify($return_url, CODENDI_PURIFIER_JS_QUOTE) . '";
}

setTimeout("return_to_url()",1000);
</script>
';
} else {
    $redirect = $Language->getText('my_redirect', 'default_txt');
}
?>

<p><big><?php echo $redirect; ?></big></p>

<?php
($pv == 2) ? $HTML->pv_footer(array()) : site_footer(array());
?>