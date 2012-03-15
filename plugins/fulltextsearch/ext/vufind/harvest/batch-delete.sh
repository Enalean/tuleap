#!/bin/sh

# Make sure VUFIND_HOME is set:
if [ -z "$VUFIND_HOME" ]
then
  echo "Please set the VUFIND_HOME environment variable."
  exit 1
fi

# Make sure command line parameter was included:
if [ -z "$1" ]
then
  echo "This script deletes records based on files created by the OAI-PMH harvester.";
  echo ""
  echo "Usage: `basename $0` [$VUFIND_HOME/harvest subdirectory]"
  echo ""
  echo "Example: `basename $0` oai_source"
  exit 1
fi

# Check if the path is valid:
BASEPATH="$VUFIND_HOME/harvest/$1"
if [ ! -d $BASEPATH ]
then
  echo "Directory $BASEPATH does not exist!"
  exit 1
fi

# Create log/processed directories as needed:
if [ ! -d $BASEPATH/processed ]
then
  mkdir $BASEPATH/processed
fi

# Process all the files in the target directory:
cd $VUFIND_HOME/util
for file in $BASEPATH/*.delete
do
  echo "Processing $file ..."
  php deletes.php $file flat
  mv $file $BASEPATH/processed/`basename $file`
done
