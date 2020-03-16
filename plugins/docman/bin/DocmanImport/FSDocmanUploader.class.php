#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
  * Script to upload an entire block of folders & files
  * into Codendi document manager.
  *
  * usage: DocUploader project_id source_dir id_dest
  *
  *  where:
  *  -project is your project shortname (where you need to upload  documents)
  *  -project_id is your project ID
  *  -source_dir is the local path to the directory containing documents you need to upload
  *  -id_destination is the id of the folder (in codendi doc manager)
  */

function getChunk($filePath, $offset, $size)
{
    return base64_encode(file_get_contents($filePath, null, null, $offset * $size, $size));
}

function uploadAllowed($name)
{
    $allowed = true;
    // Don't upload hidden files
    if ($name[0] == '.') {
        $allowed = false;
    }

    // Don't upload backup files
    $tildaEnd = strrpos($name, '~');
    if ($tildaEnd && $tildaEnd == strlen($name) - 1) {
        $allowed = false;
    }
    return $allowed;
}

if (count($argv) != 4) {
    die("usage: DocmanUploader.php project_id source_dir id_destination \n");
}

// Gather some inputs
echo "Login : ";
$login = fgets(STDIN);
$login = substr($login, 0, strlen($login) - 1);

echo "Password for $login : ";
if (PHP_OS != 'WINNT') {
    shell_exec('stty -echo');
    $password = fgets(STDIN);
    shell_exec('stty echo');
} else {
    $password = fgets(STDIN);
}
$password = substr($password, 0, strlen($password) - 1);
echo PHP_EOL;

$chunkSize = 6000000;

$project_id = $argv[1];
$source_dir = $argv[2];
$id_dest    = $argv[3];

// Server URL where all the stuff will be uploaded
$codendi_url = "";
$soap_url  = "http://serveur.codendi/soap/codendi.wsdl.php?wsdl";

//SOAP Authentication
$soap = new SoapClient($soap_url);

try {
    $hash = $soap->login($login, $password)->session_hash;
} catch (Exception $e) {
    die("Invalid Password Or User Name\n");
}

$rii = new RecursiveIteratorIterator(
    new RecursiveCachingIterator(new RecursiveDirectoryIterator($source_dir)),
    RecursiveIteratorIterator::SELF_FIRST
);

$slashEnd = strrpos($source_dir, '/', strlen($source_dir) - 1);

if ($slashEnd) {
    $foldername = substr($source_dir, 0, strlen($source_dir) - 1);
} else {
    $foldername = $source_dir;
}

$folderhash[$foldername] = $id_dest;

foreach ($rii as $r) {
    $foldername   = $r->getPathName();
    $folderpath   = $r->getPath();
    $name         = $r->getFilename();

    if ($r->isDir()) {
        echo "Creating " . $r->getFilename() . " folder ..... ";
        try {
            $res = $soap->createDocmanFolder($hash, $project_id, $folderhash[$folderpath], $r->getFilename(), '', "end");
        } catch (Exception $e) {
            die("This folder doesn't exist in the docman. Check out the id_destination(" . $e->getMessage() . ")" . PHP_EOL);
        }
        echo "OK" . PHP_EOL;
        $folderhash[$foldername] = $res;
    } elseif ($r->isFile()) {
        if (uploadAllowed($r->getFilename())) {
            //remove the extension to the name
            /*if (substr_count($name, '.') > 0) {
                $name = substr($r->getFilename(), 0, strrpos($r->getFilename(), '.'));
            }*/

            echo "Uploading " . $name . " ..... ";
            $fileName = basename($r->getPathname());
            $fileSize = filesize($r->getPathname());
            $fileType = shell_exec('file -bi "' . escapeshellcmd($r->getPathname()) . '"');
            try {
                $itemId = $soap->createDocmanFile($hash, $project_id, $folderhash[$folderpath], $name, '', 'end', 100, 0, array(), array(), $fileSize, $fileName, $fileType, '', 0, $chunkSize);
                if ($itemId) {
                    $offset = 0;
                    while (($chunk = getChunk($r->getPathname(), $offset, $chunkSize))) {
                        $soap->appendDocmanFileChunk($hash, $project_id, $itemId, $chunk, $offset, $chunkSize);
                        $offset++;
                    }
                }
                $uploadedMd5 = $soap->getDocmanFileMD5sum($hash, $project_id, $itemId, 1);
                if ($uploadedMd5 !== md5_file($r->getPathname())) {
                    echo "ERROR: md5 differs" . PHP_EOL;
                } else {
                    echo "OK" . PHP_EOL;
                }
            } catch (Exception $e) {
                echo 'ERROR (' . $e->getMessage() . ')' . PHP_EOL;
            }
        }
    }
}

echo "Files are correctly uploaded in the docman\n";

$soap->logout($hash);

?>
