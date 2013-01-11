<?php

$core_section = new Section('core', $release->version);
$sections     = array($core_section);
foreach ($release->sections as $section) {
    if ($section->hasSubSections()) {
        foreach ($section->sections as $subsection) {
            $sections[] = $subsection;
        }
    } else {
        $core_section->addSection($section);
    }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>Release Note Tuleap Tuleap <?= $release->version ?></title>
    </head>
    <body style="padding:0; margin:0; background:white">
        <table width="100%" border="0" cellspacing="0" cellpadding="15" bgcolor="#d6e4ea">
            <tr>
                <td align="center" valign="top">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td bgcolor="">
                                
                                <table width="100%" border="0" cellspacing="0" cellpadding="10">
                                    
                                    <tr>
                                        <td style="font-family: Arial; font-size:12px; line-height:4px; text-align:center; color:grey;">
                                            
                                        If this release note is not correctly displayed <a href="http://tuleap.com/sites/default/files/release/<?= $release->version ?>/release-note<?= $release->version ?>.html" style="color:#868686; text-decoration:underline;">click here</a>. </td>
                                        </td>
                                    </tr>
                                </table>
                            
                        </tr>
                    </table>
                    <table width="570" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="font-size:0; line-height:0;"><img src="http://tuleap.com/sites/default/files/release/<?= $release->version ?>/images/banniere<?= $release->version ?>.png" alt="" /></td>
                        </tr>
                        <tr>
                            <td align="right" style="color:black; font-size:11px; font-weight:bold; font-family:Arial; text-transform:uppercase;"><?= $release->date ?></td>
                        </tr>
                        <tr>
                            <td>
                                <br/>
                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td align="left" style="color:black; font-size:12px; font-family:Arial;">Thanks to the contributors...</td>
                                    </tr>
                                    <? foreach ($sections as $section): ?>
                                        <tr>
                                            <td>
                                                <br>
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#de5a14">
                                                    <tr>
                                                        <td>
                                                            <table width="100%" border="0" cellspacing="0" cellpadding="10">
                                                                <tr>
                                                                    <td style="font-family: Arial; font-size:16px; line-height:14px; color:#fff; font-weight:bold; text-transform:uppercase; text-align:left; "><?= $section->label ?></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <? include 'changes.php'; ?>
                                        <? foreach ($section->sections as $section): ?>
                                            <tr>
                                                <td>
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td style="font-family: Arial; font-size:12px; line-height:17px; text-align:left; color:black;"><br />
                                                                <div style="font-family: Arial; font-size:14px; line-height:14px; color:grey; font-weight:bold; text-transform:uppercase; text-align:left; "><?= $section->label ?></div><br />
                                                            </td>
                                                        </tr>
                                                        <? include 'changes.php'; ?>
                                                    </table>
                                                </td>
                                            </tr>
                                        <? endforeach; ?>
                                    <? endforeach; ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>


