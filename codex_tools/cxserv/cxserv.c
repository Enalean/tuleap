/*
 * Copyright (c) 2003, Xerox Corporation, SSTC.  All rights reserved.
 *
 * $Id$
 */
 
#include <stdio.h>
#include <pwd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <string.h>
#include <limits.h>
#include <signal.h>
#include <errno.h>
#include <sys/wait.h>
#include "cxserv.h"

#define MAXUSER 100
#define MAXLINE (PATH_MAX+MAXUSER+100)
#define WAIT_TRIES 20

void codex_server(const char* directory, const char* operation);
void help_message();
void syntax();

int main(int argc, char **argv)
{
  int c;
  char *directory = NULL;

  while ((c = getopt(argc, argv, "hd:")) != EOF) {
    switch (c) {
    case 'h':
      help_message();
      exit(0);
    case 'd':
      directory = optarg;
      break;
    default:
      syntax();
      exit(1);
    }
  }

  if (optind != argc - 1) {
    syntax();
    exit(1);
  }

  codex_server(directory, argv[optind]);
}

/*
 * Return the string or a default value if the string is
 * empty or null.
 */
const char* defaulted_string(const char* s, const char* def)
{
    return ((s != NULL && strlen(s) > 0)? s : def);
}

/*
 * Ensure that the admin file is owned by the owner of this process and
 * that the admin file is not world or group writable.
 */
int validate_admin_file(const char* file)
{
  struct stat status;
  uid_t euid = geteuid();
  char euser[MAXUSER+1];

  if (stat(file, &status) < 0) {
    perror(file);
    return 0;
  }

  if (status.st_uid != euid) {
    format_uid_name(euid, euser, sizeof euser);
    fprintf(stderr, "The admin file is not owned by %s.\n", euser);
    return 0;
  }

  if ((status.st_mode & (S_IWGRP|S_IWOTH)) != 0) {
    fprintf(stderr, "The admin file is writable by group or other.\n");
    return 0;
  }

  return 1;
}

int parse_admin_line(char* line, char *match_name, char *name, int name_max, char* dir, int dir_max)
{
  char *p;
  char *name_start;
  int name_len;
  char *dir_start;
  int dir_len;
  int cnt;

  cnt = strspn(line, " \t\r\n");
  if (line[cnt] == '#' || (cnt > 0 && line[cnt] == '\0' && line[cnt-1] == '\n')) {
    return 0;
  }

  name_start = &line[0];

  cnt = strcspn(name_start, ":");
  if (cnt == 0 || name_start[cnt] != ':') {
    fprintf(stderr, "Could not parse name from admin file line.\n");
    return -1;
  }
  name_len = cnt;

  dir_start = &name_start[cnt]+1;

  cnt = strcspn(dir_start, "\n\r");
  if (cnt == 0) {
    fprintf(stderr, "Could not parse directory from the admin file line.\n");
    return -1;
  }
  dir_len = cnt;

  cnt = strlen(dir_start + dir_len);
  if (dir_start[dir_len + cnt - 1] != '\n') {
    fprintf(stderr, "A line in the admin file is too long or does not end in a newline.\n");
    return -1;
  }

  if (*dir_start != '/') {
    fprintf(stderr, "A directory in the admin file is not an absolute path\n");
    return -1;
  }

  if (match_name != 0) {
    if (strlen(match_name) != name_len || strncmp(match_name, name_start, name_len) != 0) {
      return 0;
    }
  }

  if (name != NULL) {
    if (name_len >= name_max) {
      fprintf(stderr, "A name in the admin file is too long\n");
      return -1;
    }
    strncpy(name, name_start, name_len);
    name[name_len] = '\0';
  }

  if (dir != NULL) {
    if (dir_len >= dir_max) {
      fprintf(stderr, "A directory in the admin file is too long\n");
      return -1;
    }
    strncpy(dir, dir_start, dir_len);
    dir[dir_len] = '\0';
  }

  return 1;
}

int validate_user(const char* selected_directory, char* directory, int dir_max)
{
  FILE *fp = NULL;
  char line[MAXLINE];
  char *p;
  int result = 0;
  int uid = getuid();
  char user[MAXUSER+1];

  if (format_uid_name(uid, user, sizeof user) <= 0) {
    fprintf(stderr, "Could not format a name for user %d\n", uid);
    goto cleanup;
  }

  if (! validate_admin_file(ADMIN_FILE)) {
    goto cleanup;
  }

  fp = fopen(ADMIN_FILE, "r");
  if (fp == NULL) {
    perror(ADMIN_FILE);
    goto cleanup;
  }

  while (fgets(line, MAXLINE, fp) != NULL) {
    int status = parse_admin_line(line, user, NULL, 0, directory, dir_max);
    if (status < 0) {
      fclose(fp);
      goto cleanup;
    }
    else if (status == 0) {
      continue;
    }
    else if (selected_directory != NULL && strcmp(selected_directory, directory) != 0) {
      continue;
    }
    else {
      result = 1;
      goto cleanup;
    }
  }

  fprintf(stderr, "Could not find user %s and directory %s in the admin file\n",
	  user, defaulted_string(selected_directory, "ANY")
	  );

 cleanup:
  if (fp != NULL) {
    (void) fclose(fp);
  }
  return result;
}

/**
 * Format the PID file name.
 * Return:
 *  0 name was too long to fit.
 *  > 0 OK
 */
int get_pid_file(const char* directory, char* pid_file, int pid_max)
{
  if (snprintf(pid_file, pid_max, "%s/%s", directory, PIDFILE_SUFFIX) >= pid_max) {
    return 0;
  }
  return 1;
}

/**
 * Read the pid file, return the PID if > 0, 0 if an invalid PID, and -1
 * if the file could not be opened.
 */
int read_pid_file(const char *pid_file)
{
  FILE *fp;
  int pid;
  int count;

  fp = fopen(pid_file, "r");
  if (fp == NULL) {
    return -1;
  }
  count = fscanf(fp, "%d", &pid);

  (void) fclose(fp);

  if (count == 1 && pid > 0) {
    return pid;
  }
  else {
    return 0;
  }
}

typedef const char* const_string;


/**
 * fork and exec the HTTP server.
 * Return codes:
 * -1 error
 * >= 0 a return code as in wait(2)
 */
int start_httpd(const_string directory)
{
  const_string command[20];
  const_string* cp = command;
  char config_root[PATH_MAX+1];
  int pid;
  sigset_t block_mask;
  sigset_t old_mask;
  int result = 0;
  int status;

  if (snprintf(config_root, sizeof config_root, "%s/%s", directory, CONFDIR_SUFFIX) >= sizeof config_root) {
    fprintf(stderr, "The path to the config directory is too long\n");
    return -1;
  }

  *cp++ = HTTP_SERVER;
  *cp++ = "-d";
  *cp++ = config_root;
  *cp++ = "-f";
  *cp++ = CONFFILE_SUFFIX;
  *cp++ = NULL;

  (void) sigemptyset(&block_mask);
  (void) sigaddset(&block_mask, SIGCHLD);
  (void) sigaddset(&block_mask, SIGINT);
  (void) sigaddset(&block_mask, SIGQUIT);

  if (sigprocmask(SIG_BLOCK, &block_mask, &old_mask) < 0) {
    perror("Could not set signal mask");
    return -1;
  }

  pid = fork();
  if (pid < 0) {
    perror("Could not fork process");
    result = -1;
  }
  else if (pid == 0) {
    (void) sigprocmask(SIG_SETMASK, &old_mask, (sigset_t *)0);
    (void) execv(HTTP_SERVER, (char**)command);
    perror(command[0]);
    _exit(99);
  }
  else {
    int np;
    while ((np = waitpid(pid, &status, 0)) == 0)
      ;
    if (np < 0) {
      perror("error waiting for command to finish");
      result = -1;
    }
    else {
      result = status;
    }
  }
  (void) sigprocmask(SIG_SETMASK, &old_mask, (sigset_t *)0);

  return result;
} 

/**
 * Start the test server.
 * Return codes:
 *  < 0 error
 *  = 0 server is already running
 *  > 0 pid of newly started server
 * Details:
 *  -2 couldn't start server
 *  -1 server appeared to start, but pid file could not be read.
 */
int start_ts(const char* directory)
{
  struct stat status;
  int pid;
  char pid_file[PATH_MAX+1];
  int cc;
  int result;
  int i;

  if (! get_pid_file(directory, pid_file, sizeof pid_file)) {
    fprintf(stderr, "Could not format pid file name.\n");
    return -2;
  }

  pid = read_pid_file(pid_file);
  if (pid >= 0) {
    if (kill(pid, 0) == 0) {
      fprintf(stderr, "An Apache test server seems to be already running (pid %d)\n", pid);
      return 0;
    }
    else if (errno == ESRCH) {
      fprintf(stderr, "Warning: a PID file exists, but no Apache server is running on pid %d.\n", pid);
    }
  }

  printf("Starting Apache test server...");

  result = start_httpd(directory);
  if (result == -1) {
    fprintf(stderr, "Could not invoke the test server.\n");
    return -2;
  }
  else if (! WIFEXITED(result) || WEXITSTATUS(result) != 0) {
    fprintf(stderr, "The server did not start normally.\n");
    return -2;
  }

  for (i = 0; i < WAIT_TRIES && stat(pid_file, &status) < 0 && errno == ENOENT; i++) {
    printf(".");
    fflush(stdout);
    sleep(1);
  }

  pid = read_pid_file(pid_file);
  if (pid <= 0) {
    printf(" done (%s).\n", (pid < 0? "no pid file" : "invalid pid"));
    return -1;
  }

  printf(" done (pid %d).\n", pid);

  return pid;
}

/**
 * Stop the test server.
 * Returns:
 * < 0 error
 * = 0 server not running
 * > 0 server stopped
 */
int stop_ts(const char* directory)
{
  int pid;
  char pid_file[PATH_MAX+1];
  int i;
  struct stat status;

  if (! get_pid_file(directory, pid_file, sizeof pid_file)) {
    fprintf(stderr, "Could not format pid file name.\n");
    return -2;
  }

  pid = read_pid_file(pid_file);
  if (pid < 0) {
    fprintf(stderr, "The Apache test server is not running.\n");
    return 0;
  }
  else if (pid == 0) {
    fprintf(stderr, "The PID for the Apache test server is not valid.\n");
    return -1;
  }

  printf("Stopping Apache test server...");
  if (kill(pid, SIGTERM) < 0) {
    if (errno == ESRCH) {
      printf(" (no server is running)\n");
      return 0;
    }
    perror("Cannot stop Apache test server: ");
    return -2;
  }

  for (i = 0; i < WAIT_TRIES && stat(pid_file, &status) == 0; i++) {
    printf(".");
    fflush(stdout);
    sleep(1);
  }

  printf(" done (pid %d)\n", pid);
  return pid;
}


/**
 * Stop the test server.
 * Returns:
 * <= 0 an error
 * > 0 success, returns pid
 */
int restart_ts(const char* directory)
{
  int result;

  result = stop_ts(directory);
  if (result >= 0) {
    result = start_ts(directory);
  }
  return result;
}

/**
 * Format up a user name string for a given uid.
 * Returns:
 *  -1 could not find a name
 *  0 name didn't fit.
 *  1 all OK
 *
 * In all cases, name is a null terminated string.
 */
int format_uid_name(uid_t uid, char* name, int name_max)
{
  int len;
  struct passwd *pw;

  pw = getpwuid(uid);
  if (pw != NULL) {
    len = snprintf(name, name_max, "%s", pw->pw_name);
    return (len < name_max);
  }
  else {
    len = snprintf(name, name_max, "<uid %d>", uid);
    return -1;
  }
}
  

/**
 * Perform a number of sanity checks on the directory structure.
 */
int validate_directory(const char* directory)
{
  struct stat status;
  char pid_file[PATH_MAX+1];
  uid_t uid;
  char user[MAXUSER+1];
  uid_t euid;
  char euser[MAXUSER+1];
  struct passwd* pw;
  char conffile[PATH_MAX+1];

  uid = getuid();
  euid = geteuid();

  if (lstat(directory, &status) < 0) {
    perror(directory);
    return 0;
  }
  else if (! S_ISDIR(status.st_mode)) {
    fprintf(stderr, "%s is not a directory.\n", directory);
    return 0;
  }
  else if ((status.st_mode & S_IWOTH) != 0) {
    fprintf(stderr, "%s is writable by other.\n", directory);
    return 0;
  }
  else if (status.st_uid != getuid()
	   && status.st_uid != geteuid()
	   && status.st_uid != 0) {
    fprintf(stderr, "%s must be owned by %s, %s, or root\n", directory, euser, user);
    return 0;
  }

  if (! get_pid_file(directory, pid_file, sizeof pid_file)) {
    fprintf(stderr, "Could not format pid file name.\n");
    return 0;
  }
  else if (lstat(pid_file, &status) < 0) {
    if (errno != ENOENT) {
      perror(pid_file);
      return 0;
    }
  }
  else if (! S_ISREG(status.st_mode)) {
    fprintf(stderr, "%s is not a regular file.\n", pid_file);
    return 0;
  }
  else if ((status.st_mode & (S_IWGRP|S_IWOTH)) != 0) {
    fprintf(stderr, "%s is writable by group or other.\n", pid_file);
    return 0;
  }
  else if (status.st_uid != getuid()
	   && status.st_uid != geteuid()
	   && status.st_uid != 0) {
    fprintf(stderr, "%s must be owned by %s, %s, or root\n", pid_file, euser, user);
    return 0;
  }

  if (snprintf(conffile, sizeof conffile, "%s/%s/%s", directory, CONFDIR_SUFFIX, CONFFILE_SUFFIX) >= sizeof conffile) {
    fprintf(stderr, "The configuration file name is too long\n");
    return 0;
  }
  else if (lstat(conffile, &status) < 0) {
    perror(conffile);
    return 0;
  }
  else if (! S_ISREG(status.st_mode)) {
    fprintf(stderr, "%s is not a regular file.\n", conffile);
    return 0;
  }
  else if ((status.st_mode & S_IWOTH) != 0) {
    fprintf(stderr, "%s is writable other.\n", conffile);
    return 0;
  }
  else if (status.st_uid != getuid()
	   && status.st_uid != geteuid()
	   && status.st_uid != 0) {
    fprintf(stderr, "%s must be owned by %s, %s, or root\n", conffile, euser, user);
    return 0;
  }

  return 1;
}

/**
 * Invoke "operation" on the test server.
 */
void codex_server(const char* selected_directory, const char* operation)
{
  char directory[PATH_MAX+1];
  int euid = geteuid();
  int result = -1;

#ifndef NO_ROOT_CHECK
  if (euid != 0) {
    fprintf(stderr, "Must be root to start or stop the Apache test server\n");
    exit(1);
  }
#endif  

  if (! validate_user(selected_directory, directory, sizeof(directory))) {
    exit(1);
  }

  if (! validate_directory(directory)) {
    exit(1);
  }

  (void) setresuid(euid, euid, euid);

  if (strcmp(operation, "start") == 0) {
    result = start_ts(directory);
  }
  else if (strcmp(operation, "stop") == 0) {
    result = stop_ts(directory);
  }
  else if (strcmp(operation, "restart") == 0) {
    result = restart_ts(directory);
  }
  else {
    fprintf(stderr, "Invalid operation.\n");
    syntax();
    exit(1);
  }

  if (result <= 0) {
    fprintf(stderr, "A problem occured during the %s operation\n", operation);
    exit(1);
  }

  exit(0);
}

void help_message()
{
  fprintf(stderr, "codex_server [-h][-d directory] (start|stop|restart)\n");
}

void syntax()
{
  help_message();
}
