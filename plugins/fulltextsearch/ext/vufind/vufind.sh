#!/bin/sh
#
# Startup script for the VuFind Jetty Server under *nix systems
# (it works under NT/cygwin too).
#
# Configuration files
#
# $JETTY_HOME/etc/jetty.xml
#   If found, used as this script's configuration file, but only if
#   /etc/jetty.conf was not present. See above.
#
# Configuration variables
#
# VUFIND_HOME
#   Home of the VuFind installation.
#
# SOLR_HOME
#   Home of the Solr installation.
#
# JAVA_HOME
#   Home of Java installation.
#
# JAVA
#   Command to invoke Java. If not set, $JAVA_HOME/bin/java will be
#   used.
#
# JAVA_OPTIONS
#   Extra options to pass to the JVM
#
# JETTY_HOME
#   Where Jetty is installed. If not set, the script will try go
#   guess it by first looking at the invocation path for the script,
#   and then by looking in standard locations as $HOME/opt/jetty
#   and /opt/jetty. The java system property "jetty.home" will be
#   set to this value for use by configure.xml files, f.e.:
#
#    <Arg><SystemProperty name="jetty.home" default="."/>/webapps/jetty.war</Arg>
#
# JETTY_LOG
#   The path where Jetty will store the log files
#
# JETTY_CONSOLE
#   Where Jetty console output should go. Defaults to first writeable of
#      /dev/console
#      /dev/tty
#
# JETTY_PORT
#   Override the default port for Jetty servers. If not set then the
#   default value in the xml configuration file will be used. The java
#   system property "jetty.port" will be set to this value for use in
#   configure.xml files. For example, the following idiom is widely
#   used in the demo config files to respect this property in Listener
#   configuration elements:
#
#    <Set name="Port"><SystemProperty name="jetty.port" default="8080"/></Set>
#
#   Note: that the config file could ignore this property simply by saying:
#
#    <Set name="Port">8080</Set>
#
# JETTY_RUN
#   Where the jetty.pid file should be stored. It defaults to the
#   first available of /var/run, /usr/var/run, and /tmp if not set.
#
# JETTY_PID
#   The Jetty PID file, defaults to $JETTY_RUN/jetty.pid
#
# JETTY_ARGS
#   The default arguments to pass to jetty.
#

usage()
{
    echo "Usage: $0 {start|stop|run|restart|check|supervise} [ CONFIGS ... ] "
    exit 1
}


[ $# -gt 0 ] || usage

TMPJ=/tmp/j$$

##################################################
# Get the action & configs
##################################################

ACTION=$1
shift
ARGS="$*"
CONFIGS=""

##################################################
# Find directory function
##################################################
findDirectory()
{
    OP=$1
    shift
    for L in $* ; do
        [ $OP $L ] || continue
        echo $L
        break
    done
}

##################################################
# Set Performance options for JETTY
##################################################
#JAVA_OPTIONS="-server -Xms1048576k -Xmx1048576k -XX:+UseParallelGC -XX:NewRatio=5"
JAVA_OPTIONS="-server -Xms1024m -Xmx1024m -XX:+UseParallelGC -XX:NewRatio=5"

##################################################
# Set VUFIND_HOME
##################################################
if [ -z "$VUFIND_HOME" ]
then
  VUFIND_HOME="/usr/share/codendi/plugins/fulltextsearch/ext/vufind"
fi


##################################################
# Set SOLR_HOME
##################################################
if [ -z "$SOLR_HOME" ]
then
  SOLR_HOME="$VUFIND_HOME/../../etc/solr/apache-solr-1.4.0/example/solr"
fi


##################################################
# Set JETTY_HOME
##################################################
if [ -z "$JETTY_HOME" ]
then
  JETTY_HOME="$SOLR_HOME/.."
fi


##################################################
# Set Jetty's Logging Directory
##################################################
if [ -z "$JETTY_LOG" ]
then
    JETTY_LOG="$JETTY_HOME/logs"
fi


##################################################
# Jetty's hallmark
##################################################
JETTY_INSTALL_TRACE_FILE="start.jar"


#####################################################
# Check that jetty is where we think it is
#####################################################
if [ ! -r $JETTY_HOME/$JETTY_INSTALL_TRACE_FILE ]
then
   echo "** ERROR: Oops! Jetty doesn't appear to be installed in $JETTY_HOME"
   echo "** ERROR:  $JETTY_HOME/$JETTY_INSTALL_TRACE_FILE is not readable!"
   exit 1
fi


###########################################################
# Get the list of config.xml files from the command line.
###########################################################
if [ ! -z "$ARGS" ]
then
  for A in $ARGS
  do
    if [ -f $A ]
    then
       CONF="$A"
    elif [ -f $JETTY_HOME/etc/$A ]
    then
       CONF="$JETTY_HOME/etc/$A"
    elif [ -f ${A}.xml ]
    then
       CONF="${A}.xml"
    elif [ -f $JETTY_HOME/etc/${A}.xml ]
    then
       CONF="$JETTY_HOME/etc/${A}.xml"
    else
       echo "** ERROR: Cannot find configuration '$A' specified in the command line."
       exit 1
    fi
    if [ ! -r $CONF ]
    then
       echo "** ERROR: Cannot read configuration '$A' specified in the command line."
       exit 1
    fi
    CONFIGS="$CONFIGS $CONF"
  done
fi


##################################################
# Try to find this script's configuration file,
# but only if no configurations were given on the
# command line.
##################################################
if [ -z "$JETTY_CONF" ]
then
  if [ -f /etc/jetty.conf ]
  then
     JETTY_CONF=/etc/jetty.conf
  elif [ -f "${JETTY_HOME}/etc/jetty.conf" ]
  then
     JETTY_CONF="${JETTY_HOME}/etc/jetty.conf"
  fi
fi


##################################################
# Read the configuration file if one exists
##################################################
CONFIG_LINES=
if [ -z "$CONFIGS" ] && [ -f "$JETTY_CONF" ] && [ -r "$JETTY_CONF" ]
then
  CONFIG_LINES=`cat $JETTY_CONF | grep -v "^[:space:]*#" | tr "\n" " "`
fi


##################################################
# Get the list of config.xml files from jetty.conf
##################################################
if [ ! -z "${CONFIG_LINES}" ]
then
  for CONF in ${CONFIG_LINES}
  do
    if [ ! -r "$CONF" ]
    then
      echo "** WARNING: Cannot read '$CONF' specified in '$JETTY_CONF'"
    elif [ -f "$CONF" ]
    then
      # assume it's a configure.xml file
      CONFIGS="$CONFIGS $CONF"
    elif [ -d "$CONF" ]
    then
      # assume it's a directory with configure.xml files
      # for example: /etc/jetty.d/
      # sort the files before adding them to the list of CONFIGS
      XML_FILES=`ls ${CONF}/*.xml | sort | tr "\n" " "`
      for FILE in ${XML_FILES}
      do
         if [ -r "$FILE" ] && [ -f "$FILE" ]
         then
            CONFIGS="$CONFIGS $FILE"
         else
           echo "** WARNING: Cannot read '$FILE' specified in '$JETTY_CONF'"
         fi
      done
    else
      echo "** WARNING: Don''t know what to do with '$CONF' specified in '$JETTY_CONF'"
    fi
  done
fi


#####################################################
# Run the standard server if there's nothing else to run
#####################################################
if [ -z "$CONFIGS" ]
then
    CONFIGS="${JETTY_HOME}/etc/jetty.xml"
fi


#####################################################
# Find a location for the pid file
#####################################################
if [  -z "$JETTY_RUN" ]
then
  JETTY_RUN=`findDirectory -w /var/run /usr/var/run /tmp`
fi

#####################################################
# Find a PID for the pid file
#####################################################
if [  -z "$JETTY_PID" ]
then
  JETTY_PID="$JETTY_RUN/vufind.pid"
fi

#####################################################
# Find a location for the jetty console
#####################################################
if [  -z "$JETTY_CONSOLE" ]
then
  if [ -w /dev/console ]
  then
    JETTY_CONSOLE=/dev/console
  else
    JETTY_CONSOLE=/dev/tty
  fi
fi


##################################################
# Check for JAVA_HOME
##################################################
if [ -z "$JAVA_HOME" ]
then
    # If a java runtime is not defined, search the following
    # directories for a JVM and sort by version. Use the highest
    # version number.

    # Java search path
    JAVA_LOCATIONS="\
        /usr/java \
        /usr/bin \
        /usr/local/bin \
        /usr/local/java \
        /usr/local/jdk \
        /usr/local/jre \
        /usr/lib/jvm \
        /opt/java \
        /opt/jdk \
        /opt/jre \
    " 
    JAVA_NAMES="java jdk jre"
    for N in $JAVA_NAMES ; do
        for L in $JAVA_LOCATIONS ; do
            [ -d $L ] || continue 
            find $L -name "$N" ! -type d | grep -v threads | while read J ; do
                [ -x $J ] || continue
                VERSION=`eval $J -version 2>&1`       
                [ $? = 0 ] || continue
                VERSION=`expr "$VERSION" : '.*"\(1.[0-9\.]*\)["_]'`
                [ "$VERSION" = "" ] && continue
                expr $VERSION \< 1.5 >/dev/null && continue
                echo $VERSION:$J
            done
        done
    done | sort | tail -1 > $TMPJ
    JAVA=`cat $TMPJ | cut -d: -f2`
    JVERSION=`cat $TMPJ | cut -d: -f1`

    JAVA_HOME=`dirname $JAVA`
    while [ ! -z "$JAVA_HOME" -a "$JAVA_HOME" != "/" -a ! -f "$JAVA_HOME/lib/tools.jar" ] ; do
        JAVA_HOME=`dirname $JAVA_HOME`
    done
    [ "$JAVA_HOME" = "" ] && JAVA_HOME=

    echo "Found JAVA=$JAVA in JAVA_HOME=$JAVA_HOME"
fi

##################################################
# Determine which JVM of version >1.5
# Try to use JAVA_HOME
##################################################
if [ "$JAVA" = "" -a "$JAVA_HOME" != "" ]
then
  if [ ! -z "$JAVACMD" ]
  then
     JAVA="$JAVACMD"
  else
    [ -x $JAVA_HOME/bin/jre -a ! -d $JAVA_HOME/bin/jre ] && JAVA=$JAVA_HOME/bin/jre
    [ -x $JAVA_HOME/bin/java -a ! -d $JAVA_HOME/bin/java ] && JAVA=$JAVA_HOME/bin/java
  fi
fi

if [ "$JAVA" = "" ]
then
    echo "Cannot find a JRE or JDK. Please set JAVA_HOME to a >=1.5 JRE" 2>&2
    exit 1
fi

JAVA_VERSION=`expr "$($JAVA -version 2>&1 | head -1)" : '.*1\.\([0-9]\)'`

#####################################################
# See if JETTY_PORT is defined
#####################################################
if [ "$JETTY_PORT" != "" ]
then
  JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.port=$JETTY_PORT"
fi

#####################################################
# Add Solr values to command line
#####################################################
if [ "$SOLR_HOME" != "" ]
then
  JAVA_OPTIONS="$JAVA_OPTIONS -Dsolr.solr.home=$SOLR_HOME"
fi

#####################################################
# Set Jetty Logging Directory
#####################################################
if [ "$JETTY_LOG" ]
then
    JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.logs=$JETTY_LOG"
fi


#####################################################
# Are we running on Windows? Could be, with Cygwin/NT.
#####################################################
case "`uname`" in
CYGWIN*) PATH_SEPARATOR=";";;
*) PATH_SEPARATOR=":";;
esac


#####################################################
# Add jetty properties to Java VM options.
#####################################################
JAVA_OPTIONS="$JAVA_OPTIONS -Djetty.home=$JETTY_HOME "

#####################################################
# This is how the Jetty server will be started
#####################################################
RUN_CMD="$JAVA $JAVA_OPTIONS -jar $JETTY_HOME/start.jar $JETTY_ARGS $CONFIGS"


##################################################
# Do the action
##################################################
case "$ACTION" in
  start)
        echo "Starting VuFind ... "

        if [ -f $JETTY_PID ]
        then
            echo "Already Running!!"
            exit 1
        fi

        # Export variables for Import Tool
        export VUFIND_HOME

        echo "STARTED VuFind `date`" >> $JETTY_CONSOLE
        echo "$RUN_CMD"
        nohup sh -c "exec $RUN_CMD >>$JETTY_CONSOLE 2>&1" &
        echo $! > $JETTY_PID
        echo "VuFind running pid="`cat $JETTY_PID`
        ;;

  stop)
        PID=`cat $JETTY_PID 2>/dev/null`
        echo "Shutting down VuFind ... "
        kill $PID 2>/dev/null
        sleep 2
        kill -9 $PID 2>/dev/null
        rm -f $JETTY_PID
        echo "STOPPED `date`" >>$JETTY_CONSOLE
        ;;

  restart)
        if [ -x "$0" ]; then
            "$0" stop $*
            sleep 5
            "$0" start $*
        else
            sh "$0" stop $*
            sleep 5
            sh "$0" start $*
        fi
        ;;

  supervise)
       #
       # Under control of daemontools supervise monitor which
       # handles restarts and shutdowns via the svc program.
       #
         exec $RUN_CMD
         ;;

  run|demo)
        echo "Running VuFind ... "

        if [ -f $JETTY_PID ]
        then
            echo "Already Running!!"
            exit 1
        fi

        exec $RUN_CMD
        ;;

  check)
        echo "Checking arguments to VuFind: "
        echo "VUFIND_HOME    =  $VUFIND_HOME"
        echo "SOLR_HOME      =  $SOLR_HOME"
        echo "JETTY_HOME     =  $JETTY_HOME"
        echo "JETTY_LOG      =  $JETTY_LOG"
        echo "JETTY_CONF     =  $JETTY_CONF"
        echo "JETTY_RUN      =  $JETTY_RUN"
        echo "JETTY_PID      =  $JETTY_PID"
        echo "JETTY_CONSOLE  =  $JETTY_CONSOLE"
        echo "JETTY_PORT     =  $JETTY_PORT"
        echo "CONFIGS        =  $CONFIGS"
        echo "JAVA_OPTIONS   =  $JAVA_OPTIONS"
        echo "JAVA           =  $JAVA"
        echo "CLASSPATH      =  $CLASSPATH"
        echo "RUN_CMD        =  $RUN_CMD"
        echo

        if [ -f $JETTY_RUN/vufind.pid ]
        then
            echo "VuFind running pid="`cat $JETTY_RUN/vufind.pid`
            exit 0
        fi
        exit 1
        ;;

*)
        usage
	;;
esac

exit 0
