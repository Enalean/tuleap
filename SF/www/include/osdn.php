<?php

util_get_content('layout/osdn_sites');

function osdn_nav_dropdown() {
	GLOBAL $osdn_sites;
// LJ write the FORM directly instead of using
// document.write because Netscape 4.x gets crazy
// and doesn't know how to redraw the page when 
// window is resized.
?>
	<!-- OSDN navdropdown -->
	    <script language=javascript>
	    function handle_navbar(index,form) {
	        if ( index > 1 ) {
	            window.location=form.options[index].value;
	        }
	    }
	    </script>
        <form name=form1>
        <font size=-1>
        <a href="<?php print 'http://'.$GLOBALS['sys_default_domain']; ?>"><?php echo html_image("codex_logo.png",array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0")); ?></A><br>
        <select name=navbar onChange="handle_navbar(selectedIndex,this)">
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
        <a href="<?php print $GLOBALS['sys_default_domain']; ?>"><img src="/images/codex_logo.png" width="135" height="33" hspace="10" alt="<?php print $GLOBALS['sys_default_domain']; ?>"  border="0"></A><br>
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
';

    $motd = getenv('SF_LOCAL_INC_PREFIX').'/etc/motd.inc';
    if (file_exists($motd) && filesize($motd)>0 ) {
	$fp = fopen($motd,"r");
	$output = fread($fp,200000);
	echo $output;
	fclose($fp);
    } else {
	print '	    <SPAN class="osdn">
                                       <font color="#ffffff">Network Gallery&nbsp;:&nbsp;</font>';
	osdn_print_randpick($GLOBALS['osdn_sites'], 5);
	print '	</SPAN>';
    }

// LJ
print '	     </td>';

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
