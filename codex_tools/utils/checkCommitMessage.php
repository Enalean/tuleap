#!/usr/bin/php -q
<?

/**
 * PHP file called by the pre-commit hook
 * Check if the commit message respect the convention message
 * about metadata (level of criticality, need a manual update, etc ...)
 *
 * Import the model class of metadata, so the algorithm to parse the message is the same
 */
 
include("/usr/share/codex/plugins/serverupdate/include/SVNCommitMetaData.class");

$metadata = new SVNCommitMetaData();

// open the standard error output
$stderr = fopen('php://stderr', 'w');

$metadata->setMetaData($argv[1]);

if ($metadata->getLevel() === null) {
   // One of the meta data is missing.
   fwrite($stderr, "\nThe 'level' metadata is missing in the commit message.\n");
   fwrite($stderr, "\n\nUsage:\n");
   fwrite($stderr, "level=");
   $levels = $metadata->getAvailableLevels();
   fwrite($stderr, implode("|", $levels));
   fwrite($stderr, "\t\tMandatory\n");
   fwrite($stderr, "update=db\t\tOptional, The commit implies db modifications.\n");
   fclose($stderr);
   exit(1);
}
fclose($stderr);

?>
