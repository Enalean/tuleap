#!/bin/bash
# $Id: index_file.sh 17 2008-06-20 14:40:13Z wayne.graham $
#
# Bash script to start the import of a binary marc file for Solr indexing.
#
# VUFIND_HOME
#	Path to the vufind installation
# SOLRMARC_HOME
#	Path to the solrmarc installation
# JAVA_HOME
#	Path to the java
# INDEX_OPTIONS
#	Options to pass to the JVM
#

E_BADARGS=65
EXPECTED_ARGS=1

if [ $# -ne $EXPECTED_ARGS ]
then
  echo "    Usage: `basename $0` ./path/to/marc.mrc"
  exit $E_BADARGS
fi

##################################################
# Set INDEX_OPTIONS
# 	Tweak these in accordance to your needs
# Xmx and Xms set the heap size for the Java Virtual Machine
# You may also want to add the following:
# -XX:+UseParallelGC
# -XX:+AggressiveOpts
##################################################
INDEX_OPTIONS='-Xms512m -Xmx512m'


##################################################
# Set SOLRCORE
##################################################
if [ -z "$SOLRCORE" ]
then
  SOLRCORE="biblio"
fi


##################################################
# Set SOLR_HOME
##################################################
if [ -z "$SOLR_HOME" ]
then
  if [ -z "$VUFIND_HOME" ]
  then
    echo "You need to set the VUFIND_HOME environmental variable before running this script."
    exit 1
  else
    SOLR_HOME="$VUFIND_HOME/solr"
  fi
fi


##################################################
# Set SOLRMARC_HOME
##################################################
if [ -z "$SOLRMARC_HOME" ]
then
  SOLRMARC_HOME="$VUFIND_HOME/import/solrmarc"
fi


#####################################################
# Build java command
#####################################################
if [ "$JAVA_HOME" ]
then
  JAVA="$JAVA_HOME/bin/java"
else
  JAVA="java"
fi


##################################################
# Set Command Options
##################################################
JAR_FILE="$VUFIND_HOME/import/@CUSTOM_JAR_NAME@"
PROPERTIES_FILE="vufind_config.properties"
ERROR_LOG="import/error-log"
IMPORT_LOG="import/import-log"
SOLRWARLOCATIONORJARDIR=%VUFIND_HOME%/solr/jetty/webapps/solr.war
TEST_SOLR_JAR_DEF=@SOLR_JAR_DEF@
SOLR_JAR_DEF=`echo $TEST_SOLR_JAR_DEF | sed -e"s|-Done-jar.class.path=.*|-Done-jar.class.path=\"$SOLRWARLOCATIONORJARDIR\"|"`

#####################################################
# Execute Importer
#####################################################

pushd $SOLRHOME
RUN_CMD="$JAVA $INDEX_OPTIONS $SOLR_JAR_DEF -Dsolr.core.name=$SOLRCORE -Dsolrmarc.path=$SOLRMARC_HOME -Dsolr.path=$SOLR_HOME -jar $JAR_FILE $PROPERTIES_FILE $1"
exec > $IMPORT_LOG
exec 2> $ERROR_LOG
echo "Now Importing $1 ..."
exec $RUN_CMD
popd

exit 0
