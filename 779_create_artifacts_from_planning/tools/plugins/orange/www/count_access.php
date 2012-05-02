<?php
//
// Author : Christian Bayle
// License : GPL V2 and next
// 

require_once('pre.php');
function display($label,$date){
	echo "<td>$label</td>";
	$datestr=date('j/m/y H:i',$date); 
	echo "<td>$datestr</td>";
	$res=db_query("SELECT count(*) access FROM user WHERE last_access_date >= $date");
	if (db_numrows($res)) { $cntlast=db_result($res,0,'access'); }
	echo "<td>$cntlast</td>";
	$res=db_query("SELECT count(*) access FROM user WHERE last_access_date >= $date AND email LIKE '%@orange.com'");
	if (db_numrows($res)) { $cntft=db_result($res,0,'access'); }
	$percentft=round($cntft*100/$cntlast,2);
	echo "<td>$cntft en @orange.com [$percentft %]</td>";
	$res=db_query("SELECT count(*) access FROM user WHERE last_access_date >= $date AND email LIKE '%.ext@orange.com'");
	if (db_numrows($res)) { $cntext=db_result($res,0,'access'); }
	$percentext=round($cntext*100/$cntlast,2);
	echo "<td>dont $cntext en .ext@orange.com [$percentext %]</td>";
	$cntother=$cntlast-$cntft;
	$percentother=round($cntother*100/$cntlast,2);
	echo "<td>et autres $cntother [$percentother %]</td>";
}

//$HTML->header(array('title'=>'Access Count'));

//$lastday  = mktime()-24*3600;
$lastday =    mktime(date("H"), date("i"), date("s"), date("m"),   date("d")-1, date("Y"));
$lastweek =   mktime(date("H"), date("i"), date("s"), date("m")  , date("d")-7, date("Y"));
$lastmonth =  mktime(date("H"), date("i"), date("s"), date("m")-1, date("d"),   date("Y"));
$last2month = mktime(date("H"), date("i"), date("s"), date("m")-2, date("d"),   date("Y"));
$last3month = mktime(date("H"), date("i"), date("s"), date("m")-3, date("d"),   date("Y"));
$lastyear =   mktime(date("H"), date("i"), date("s"), date("m"),   date("d"),   date("Y")-1);

$res=db_query("SELECT min(last_access_date) access FROM user WHERE last_access_date != 0");
if (db_numrows($res)) { $oldestaccess=db_result($res,0,'access'); }
$oldestaccessstr = date('j/m/y H:i',$oldestaccess);
?>

<br>Nous sommes le : <?php echo date('j/m/y H:i');?>
<br>Acc&egrave;s le plus ancien connu : <?php echo $oldestaccessstr;?><br>
<br>Nombre d'utilisateurs diff&eacute;rents s'&eacute;tant connect&eacute;s &agrave; CodeX depuis : <br>
<table border=1>
	<tr><?php display("hier",$lastday);?></tr>
	<tr><?php display("la semaine derni&egrave;re",$lastweek);?></tr>
	<tr><?php display("le mois dernier",$lastmonth);?></tr>
	<tr><?php display("les deux derniers mois",$last2month);?></tr>
	<tr><?php display("les trois derniers mois",$last3month);?></tr>
	<tr><?php display("l'ann&eacute;e pass&eacute;e",$lastyear);?></tr>
</table>

<?php //$HTML->footer(array()); ?>
