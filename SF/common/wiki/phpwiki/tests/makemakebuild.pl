#!/usr/bin/perl

#  Write out a Makefile and a build.xml file based on the *.inputs files
#  in the current directory. Steve Wainstead, April 2001.

# $Id$

# read in all the input files, loop over each one and build up 
# text blocks that we will subsitute into the skeletons for Makefile
# and build.xml.

# reqirements: 
#  sun's java sdk, http://java.sun.com/j2se/
#  httpunit,       http://httpunit.sf.net
#  ant,            http://jakarta.apache.org/builds/jakarta-ant/release/

# usage:
#  copy the httpunit jars to this path or add them to your CLASSPATH
#  fix the url below for your server
#  run makemakebuild.pl, this creates Makefile (gnu make) and build.xml (ant)
#  run make, this compiles the classes and runs ant. 
#  if your classpath is wrong run ant seperately to test.
#  run ant for each test. both ant and make can run independently.

#my $my_wikiurl = 'http://reini/phpwiki/';  # this will replace steve's url below if defined
#-----------------------------------------

my $ori_wikiurl = 'http://127.0.0.1:8080/~swain/phpwiki/';
my @files = <*.inputs>;
chomp(@files); # prolly unnecessary, but oh well.

print "Found ", scalar(@files), " input files.\n";

foreach $inputfile (@files) {
  $inputfile =~ m/\.inputs$/;
  $javafile = "$`.java";
  $classname = $`;
  if ($my_wikiurl and ($my_wikiurl ne $ori_wikiurl)) {
    local $/;
    open IN, "< $inputfile";
    $contents = <IN>;
    `perl -i.orig -pe 's|$ori_wikiurl|$my_wikiurl|' $inputfile` if $contents =~ m|$ori_wikiurl|;
  }

  $test_make_target_names .= "$javafile ";
  $test_make_targets .=<<"EOLN";
$javafile: $inputfile
\tmaketest.pl $inputfile

EOLN

  $test_ant_targets .= <<"EOLN";
  <target name="$classname">
    <echo message="Testing with $classname..."/>
    <java classname="$classname"></java>
  </target>

EOLN

  push @test_dependency_names, $classname;

}

$test_dependency_names = join(',', @test_dependency_names);

#  print <<"SHOW_RESULTS";
#    make's targets: $test_make_target_names

#    make's acutual targets:
#  $test_make_targets

#    ant's target names: $test_dependency_names

#    ant's targets:
#  $test_ant_targets

#  SHOW_RESULTS


# these are the skeleton files for the Makefile and the build.xml file

$makefile = <<MAKEFILE_SKEL;
# Generate new test classes if their input files have changed.
# This makefile is called from an Ant build.xml though you can run
# it by hand.

.SUFFIXES: .inputs .java .class .zip 
.PHONY: all clean buildtests dotest

tests = $test_make_target_names

# ANT_HOME=P:\\ant # path style os dependent!
CLASSPATH="httpunit.jar:Tidy.jar:classes.zip"

testsrc = \$(wildcard *.inputs)
javas   = \$(testsrc:.inputs=.java)
classes = \$(javas:.java=.class)
tests   = \$(javas:.java=)

all: buildtests classes.zip dotest

dotest: \$(classes)
\texport CLASSPATH=\$(CLASSPATH)
\tant 
#\tjava -classpath "\$(CLASSPATH):\${ANT_HOME}\\lib\\ant.jar" -Dant.home="\${ANT_HOME}" org.apache.tools.ant.Main \$(<:.class=)

buildtests: \$(javas) classes.zip

classes.zip: \$(classes)
\tzip \$@ \$?

clean:
\t-rm -f \$(javas) \$(classes) classes.zip

%.java : %.inputs
\tmaketest.pl \$<

%.class : %.java
\tjavac -classpath httpunit.jar \$<

MAKEFILE_SKEL


$buildxml = <<"BUILDXML_SKEL";
<project name="test" default="all">
	
   <target 
      name="all"
      depends="init,generate,compile,test">
   </target>


   <target name="init">
      <tstamp/>
   </target>

   <target name="generate" depends="init">
      <exec executable="make">
         <arg line="buildtests"/>
      </exec>
   </target>



   <target name="compile" depends="generate">
      <javac srcdir="." destdir="." />
   </target>


   <target name="test" depends="compile,$test_dependency_names">
   </target>


   <target name="clean">

      <exec executable="make">
         <arg line="clean"/>
      </exec>

      <delete>
         <fileset dir="." includes="*.class"/>
      </delete>

   </target>


   <!-- individual test files are compiled here -->

$test_ant_targets

</project>
BUILDXML_SKEL


print "Writing Makefile...\n";
open MAKEFILE, ">./Makefile" or die $!;
print MAKEFILE $makefile;

print "Writing build.xml...\n";
open BUILDXML, ">./build.xml" or die $!;
print BUILDXML $buildxml;

print "Done.\n";
