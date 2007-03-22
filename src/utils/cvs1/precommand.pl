#!/usr/bin/perl -s
#only use for import command
#get the module name to import and write it in a temporary file for the postcommand file

#
#      Subroutines
#
sub write_line {
    local($filename, $line) = @_;
    open(FILE, ">$filename") || die("Cannot open $filename, stopped");
    print(FILE $line, "\n");
    close(FILE);
}


#
#      Main Body 
#
if (@ARGV[1] eq 'import'){
    @args = split(' ',@ARGV[2]);
    @repos = split('/', @ARGV[0]);
    $long = @args;
    $module = @args[$long-3];
    $TMPDIR = "/var/run/log_accum";
    $id = getpgrp();                # You *must* use a shell that does setpgrp()!
    $temp_name = @repos[$#repos];
    $FILE_NAME     = sprintf ("$TMPDIR/#%s.moduleImport", $temp_name);
    &write_line("$FILE_NAME.$id", $module);
}
