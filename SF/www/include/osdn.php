<?php
$osdn_sites[0] = array('XRCE' => 'http://www.xrce.xerox.com/');
$osdn_sites[1] = array('PARC' => 'http://parcweb.parc.xerox.com/');
$osdn_sites[2] = array('XR&amp;T' => 'http://www.research.xerox.com/');
$osdn_sites[3] = array('Open Source at Xerox' => 'http://opensource.codex.xerox.com/');
$osdn_sites[4] = array('Linux at Xerox' => 'http://xww.linux.world.xerox.com/');
$osdn_sites[5] = array('Wilson Center' => 'http://techweb.wrc.xerox.com/');
$osdn_sites[6] = array('Slashdot.Org' => 'http://www.slashdot.com/');
$osdn_sites[7] = array('SourceForge' => 'http://www.sourceforge.net/');
$osdn_sites[8] = array('Freshmeat' => 'http://www.freshmeat.net/');
$osdn_sites[9] = array('SSTC' => 'http://xww.xac.world.xerox.com/');
$osdn_sites[10] = array('Xerox eTTM' => 'http://ettm.wrc.xerox.com/');
$osdn_sites[11] = array('Wilson TIC' => 'http://tic.wrc.xerox.com/');
$osdn_sites[12] = array('alphaAvenue' => 'http://alphax.wrc.xerox.com/');

function osdn_nav_dropdown() {
	GLOBAL $osdn_sites;
// LJ write the FORM directly instead of using
// document.write because Netscape 4.x gets crazy
// and doesn't know how to redraw the page when 
// window is resized.
?>
	<!-- OSDN navdropdown -->
        <form name=form1>
        <font size=-1>
        <a href="<?php print $GLOBALS['sys_default_domain']; ?>"><?php echo html_image("codex_logo.gif",array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0")); ?></A><br>
        <select name=navbar onChange="window.location=this.options[selectedIndex].value">
        <option value="<?php print $GLOBALS['sys_default_domain']; ?>/gallery.html">Network Gallery</option>
        <option>------------</option>
<?php
        reset ($osdn_sites);
        while (list ($key, $val) = each ($osdn_sites)) {
        	list ($key, $val) = each ($val);
		print "\n   <option value=\"$val\">$key</option>";
        }
?>
        </select>
	</font>
        </form>

        <noscript>
        <a href="<?php print $GLOBALS['sys_default_domain']; ?>"><img src="/images/codex_logo.gif" width="135" height="33" hspace="10" alt="<?php print $GLOBALS['sys_default_domain']; ?>"  border="0"></A><br>
        </noscript>
	<!-- end OSDN navdropdown -->
<?php
}

/*
	Picks random OSDN sites to display
*/
function osdn_print_randpick($sitear, $num_sites = 1) {
	shuffle($sitear);
	reset($sitear);
        while ( ( $i < $num_sites ) && (list($key,$val) = each($sitear)) ) {
		list($key,$val) = each($val);
		print "\t\t&nbsp;&middot;&nbsp;<a href='$val'style='text-decoration:none'><font color='#ffffff'>$key</font></a>\n";
		$i++;
	}
	print '&nbsp;&middot;&nbsp;';
}

function osdn_print_navbar() {
	print '
<!-- OSDN navbar -->
<table width="100%" cellpadding="2" cellspacing="0" border="0" bgcolor="#bcbcad">
	<tr> 
		<td valign="middle" align="left">
		<SPAN class="osdn">
<!-- LJ			<font face="arial,geneva,helvetica,verdana,sans-serif" size="-2" color="#ffffff">&nbsp;&nbsp;&nbsp;<b><a href="http://osdn.com/" style="text-decoration:none"><font color="#ffffff">O&nbsp;<font color="#9b9b9b">|</font>&nbsp;S&nbsp;<font color="#9b9b9b">|</font>&nbsp;D&nbsp;<font color="#9b9b9b">|</font>&nbsp;N</font></a>&nbsp;:&nbsp;
-->
<font color="#ffffff">Network Gallery&nbsp;:&nbsp;</font>';

	osdn_print_randpick($GLOBALS['osdn_sites'], 5);

// LJ
print '		</SPAN>
		</td>';

/* LJ	print '
		</SPAN>
		</td>
		<td valign="middle" align="right" bgcolor="#6C7198">
		<SPAN class="osdn">
			<b><a href="http://www.osdn.com/index.pl?indexpage=myosdn" style="text-decoration:none"><font color="#ffffff">My OSDN</font></a>&nbsp;&middot;&nbsp;
';
LJ */


/*
		<a href="" style="text-decoration:none"><font color="#ffffff">JOBS</font></a>&nbsp;&middot;&nbsp;
*/

/* LJ	print '
	<a href="http://www.osdn.com/partner_programs.shtml" style="text-decoration:none"><font color="#ffffff">PARTNERS</font></a>&nbsp;&middot;&nbsp; 
		<a href="http://www.osdn.com/gallery.pl?type=community" style="text-decoration:none"><font color="#ffffff">AFFILIATES</font></a>&nbsp;</b></font>
		</SPAN>
		</td>
	</tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tbody> 
	<tr> 
		<td valign="center" align="center" width="100%" bgcolor="#d5d7d9" background="/images/steel3.jpg">
'; 
LJ */


	srand((double)microtime()*1000000);
        $random_num=rand(0,100000);

	if (session_issecure()) {
		$_SSL='s';
	} else {
		$_SSL='';
	}
//(LJ)	print '<a href="http'.$_SSL.'://www2.valinux.com/adbouncer.phtml?f_s=468x60&f_p=1&f_RzXx='.$random_num.'"><img src="http'.$_SSL.'://www2.valinux.com/adserver.phtml?f_s=468x60&f_p=1&f_RzXx='.$random_num.'" width="468" height="60" border="0" alt=" Advertisement "></a></td>
	print '
<!-- LJ <td valign="center" align="left" bgcolor="#d5d7d9" background="/images/steel3.jpg"><a href="http://www.osdn.com"><img src="/images/OSDN-lc.gif" width="100" height="40" hspace="10" border="0" alt=" OSDN - Open Source Development Network "></a> -->
	<!--LJ/td-->
	</tr>
	<!--/tbody--> 
</table>

<!-- End OSDN NavBar -->
';
}
?>
