#!/usr/bin/perl -s
#only use for import command
#get the module name to import from the temporary file create by precommand.pl
#checkout of the modulein a temporary directory, apply cvs watch on and remove the directory 

$utils_path = $ENV{'CODEX_UTILS_PREFIX'} || "/usr/share/codex/src/utils";
require $utils_path."/cvs1/cvs_watch.pl";


#
#      Subroutines
#
sub read_line {
    local($line);
    local($filename) = @_;

    open(FILE, "<$filename") || die("Cannot open file <$filename $!.\n");
    $line = <FILE>;
    close(FILE);
    chop($line);
    $line;
}


#
#      Main Body 
#
if (@ARGV[1] eq 'import'){
    $TMPDIR = "/var/run/log_accum";
    @repos = split('/', @ARGV[0]);
    $temp_name = @repos[$#repos];
    $FILE_NAME     = sprintf ("$TMPDIR/#%s.moduleImport", $temp_name);
    $id = getpgrp();                # You *must* use a shell that does setpgrp()!
    $module = &read_line("$FILE_NAME.$id");
    unlink ($FILE_NAME.".".$id);
    print("The system is setting your new module in watch on mode. Please wait...\n");
    &cvs_watch(@ARGV[0],$temp_name,$module,$id,1);
}
