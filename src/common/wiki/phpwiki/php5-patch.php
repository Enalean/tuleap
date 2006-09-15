<?php
  // ease PHP5 source file updates:
  // each line with the special tag '/*PHP5 patch*/' will be commented or uncommented.

function check_php_version ($a = '0', $b = '0', $c = '0') {
  global $PHP_VERSION;
  if(!isset($PHP_VERSION))
    $PHP_VERSION = substr( str_pad( preg_replace('/\D/','', PHP_VERSION), 3, '0'), 0, 3);
  return $PHP_VERSION >= ($a.$b.$c);
}
function patch_into5 ($file) {
  patch($file,'/^(\s+)(\/\*PHP5 patch\*\/)/',"\$1// \$2",'.php4');
}
function patch_into4 ($file) {
  patch($file,'/^(\s+)\/\/ (\/\*PHP5 patch\*\/)/',"\$1\$2",'.php5');
}
function patch ($file,$from,$to,$bak='.bak') {
  $tmpfile = $file.".tmp";
  $in  = fopen($file,"rb");
  $out = fopen($tmpfile,"wb");
  $changed = 0;
  while (!feof($in)) {
    $s = fgets($in);
    $new = preg_replace($from,$to,$s,1);
    if ($new != $s) $changed++;
    fputs($out,$new);
  }
  fclose($in);
  fclose($out);
  if ($changed) {
    if (file_exists($file.$bak))
      unlink($file.$bak);
    rename($file,$file.$bak);
    rename($tmpfile,$file);
    echo "successfully patched $file, backup: $file$bak\n";
  } else {
    unlink($tmpfile);
    echo "didn't patch $file\n";
  }
}

$dir = dirname(__FILE__);
$patchfiles = array("lib/WikiUserNew.php");

// patch into which direction?
if (check_php_version(5)) $patchfunc = 'patch_into5';
else $patchfunc = 'patch_into4';

foreach ($patchfiles as $f) {
  $file = $dir . "/" . $f;
  if (!file_exists($f))
    trigger_error("File $f not found");
  $patchfunc($f);
}

?>