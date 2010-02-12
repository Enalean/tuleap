#!/usr/bin/perl

use Getopt::Std;

#arg parsing
%options = ();
getopts('r:l:p:', \%options);

my $from_rev         = '';
my $to_rev           = '';
my $repository       = '';

($from_rev,$to_rev)     = split(/:/, $options{r}) if defined $options{r};
$repository             = $options{p} if defined $options{p};

$from_rev               = int $from_rev;
$to_rev                 = int $to_rev;

#check rev range
if ( !$from_rev || !$to_rev ) {
    die( &usage );
}

if ( ! -d $repository  ) {
   die( '[ERROR] Repository directory does not exist' );
}

my $answer;
print STDERR '[WARNING] Notification will be sent, check you deactivate it before running this script, continue ? [y/N]: ';
chomp($answer = <STDIN>);
close(STDIN);
if ( $answer ne 'y' ) {
    die('Aborted');
}

for ( my $i=$from_rev; $i<$to_rev+1; $i++ ) {
    print 'Processing revision '."$i\n";
    `perl /usr/share/codendi/src/utils/svn/commit-email.pl $repository $i`;
}

# functions
sub usage {
  return "Usage : $0 -r rev1:rev2 -p repo_sys_path\n";
}

     
1;
