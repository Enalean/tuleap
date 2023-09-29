<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
    $HTML->pv_header([]);
} else {
    $pv = 0;
    site_header(\Tuleap\Layout\HeaderConfiguration::fromTitle(_('Redirecting...')));
}

$vReturnTo = new Valid_String('return_to');
$vReturnTo->required();
if ($request->valid($vReturnTo)) {
    // Re-serialize feedback to display it on the 'return_to' page.
    $HTML->_serializeFeedback();

    $url_verifier = new URLVerification();
    $return_url   = '/';
    if ($url_verifier->isInternal($request->get('return_to'))) {
        $return_url = $request->get('return_to');
    }

    $redirect = sprintf(
        _('Requested page currently loading. If it does not, try <a href="%1$s">%1$s</a>.'),
        $hp->purify($return_url, CODENDI_PURIFIER_CONVERT_HTML)
    );

    print '
<script type="text/javascript">
function return_to_url() {
  window.location="' . $hp->purify($return_url, CODENDI_PURIFIER_JS_QUOTE) . '";
}

setTimeout("return_to_url()",1000);
</script>
';
} else {
    $redirect = _('You are on the redirection page. You may found useful links on your <a href="/my">personal page</a>.');
}
?>

<section class="empty-state-page">
    <svg width="320" height="220" viewBox="0 0 320 220" fill="none" class="empty-state-illustration" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#clip0)">
            <path d="M319 159.5C319 247.589 247.589 319 159.5 319C71.4106 319 0 247.589 0 159.5C0 71.4106 71.4106 0 159.5 0C247.589 0 319 71.4106 319 159.5Z" fill="url(#paint0_linear)" />
            <path fill-rule="evenodd" clip-rule="evenodd" d="M196.879 104.424L174.247 110.153C160.251 113.697 150.448 126.291 150.448 140.729V178.507C150.448 179.99 149.246 181.192 147.763 181.192H135.684C134.202 181.192 133 179.99 133 178.507V140.729C133 118.304 148.226 98.743 169.965 93.2395L192.597 87.51L196.879 104.424Z" fill="var(--tlp-illustration-main-color)" />
            <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M147.763 85.901C149.246 85.901 150.448 87.1028 150.448 88.5852L150.448 161.06C150.448 162.542 149.246 163.744 147.763 163.744L141.724 163.744L135.684 163.744C134.202 163.744 133 162.542 133 161.06L133 88.5852C133 87.1028 134.202 85.901 135.684 85.901L147.763 85.901Z" fill="var(--tlp-illustration-main-color)" />
            <path d="M177.411 120.965C176.955 123.464 179.928 125.117 181.811 123.412L216.733 91.7672C218.356 90.2965 217.603 87.6041 215.453 87.1883L169.815 78.3649C167.321 77.8828 165.637 80.8375 167.322 82.7376L180.618 97.7321C181.16 98.3431 181.391 99.1693 181.244 99.9727L177.411 120.965Z" fill="var(--tlp-illustration-main-color)" />
        </g>
        <defs>
            <linearGradient id="paint0_linear" x1="159.5" y1="0" x2="159.5" y2="188.981" gradientUnits="userSpaceOnUse">
                <stop offset="0.416" stop-color="var(--tlp-illustration-grey-on-background)" />
                <stop offset="1" stop-color="var(--tlp-illustration-grey-on-background)" stop-opacity="0" />
            </linearGradient>
            <clipPath id="clip0">
                <rect width="320" height="220" fill="white" />
            </clipPath>
        </defs>
    </svg>
    <h1 class="empty-state-title"><?php echo _('You are going be redirected'); ?></h1>
    <p class="empty-state-text"><?php echo $redirect; ?></p>
</section>

<?php
($pv == 2) ? $HTML->pv_footer() : site_footer([]);
?>
