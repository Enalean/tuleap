#!/usr/bin/perl
#######################################################################
###                  DocmanLegacyDownloader.pl                      ###
#######################################################################
#                                                                     #
# Script to download documents from the legacy docman to              #
# 'project_documentation' dir.                                        #
#                                                                     #
# you will be asked to type                                           #
#   -user: the user to access the database                            #
#   -password                                                         #
#   -group_id is the group_id of your project                         #
#                                                                     #
# NB: if you are not using this script on codex.cro.st.com            #
#     you have to change in the line 41 'host=codex.cro.st.com'       #
#     by your server name.                                            #
#                                                                     #
# Author : Hamouda LAYOUNI                                            #
# email  : hamouda.layouni@st.com                                     #
#######################################################################

use strict;
use DBI;
use Term::ReadKey;

my $_codex_server="codex.xerox.com";
my $_codex_db="codex";

print "Database User : ";
ReadMode('normal');
my $user = ReadLine(0);
chomp ($user);

print "Password for $user :";
ReadMode('noecho');
my $passwd = ReadLine(0);
chomp ($passwd);

print "\nEnter the group_id of the project :";
ReadMode('normal');
my $group_id = ReadLine(0);
chomp ($group_id);
my $source ="dbi:mysql:dbname=$_codex_db;host=$_codex_server";
my $base = DBI->connect($source,$user,$passwd) or die DBI::errstr;

print "Creating directory: project_documentation \n";
`mkdir project_documentation`;
chdir ('project_documentation');

my $sql_doc_groups = "select * from doc_groups where group_id = $group_id";


my $req_doc_groups=$base->prepare($sql_doc_groups) or die($base->errstr());
$req_doc_groups->execute() or die($base->errstr());

while (my @z =$req_doc_groups->fetchrow_array())
{
    print " ---- Creating directory :$z[1]  \n";
    `mkdir $z[1]`;
    chdir ($z[1]);
    my $sql = "select data,filename from doc_data where doc_group=$z[0]";
    my $req=$base->prepare($sql) or die($base->errstr());
    $req->execute() or die($base->errstr());
    while (my @t =$req->fetchrow_array())
    {
        print " -------- Creating file : $t[1] \n";
        open(FILE,">$t[1]");
        binmode(FILE);
        print FILE $t[0];
        close(FILE);
    }
    chdir ('../');

}

