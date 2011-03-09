/*
 * Small Tool to move files from the FTP incoming dir
 * to the project ftp space.  Runs +suid.
 *
 * 
 */
#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <fcntl.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>

int legal_string (char* test_string) {
// We have commented thios function because 
// it seems to be useless nowadays. 
// Proper checking is already done in PHP code
  /* test for legal characters:
     -./0-9  45-57
     A-Z     65-90
     a-z     97-122
   */

//  int i;
//
//  for (i = 0; i < strlen(test_string); i++) {
//   if ( (test_string[i] < 43) || (test_string[i] == 44) ||
//	 ((test_string[i] > 57) && (test_string[i] < 65)) ||
//	 ((test_string[i] != 95) && (test_string[i] > 90) && (test_string[i] < 97)) ||
//	 (test_string[i] > 122) ) {
//      printf("%c", test_string[i]);
//      return 0;
//    } /* if */
//  } /* for */

//  /* test for illegal combinations of legal characters: ".." */
//  if (strstr(test_string, "..")) {
//    return 0;
//  } /* if */

  return 1;
} /* legal_string */

int get_dirname(char *ch)
{
    char* s;
    s=ch+strlen(ch)-1;
    while (s && *s == '/') {
        *s = '\0';
        s=ch+strlen(ch)-1;
    }
    s = strrchr(ch, '/');
    if (s && *s)
        *s = '\0';
    return(0);
}

int main (int argc, char** argv) {

  /* edit me */
  char* src_dir   = "/var/lib/codendi/ftp/incoming/";
  char* dest_dir  = "/var/lib/codendi/ftp/codendi/";

  /* don't edit me (unless mv isn't in /bin) */
  char* move_path = "/bin/mv";
  char* move_file = "mv";
  char* dest_file;
  char* dirname;
  char* src_file;

  struct stat buf;

  if (argc != 3) {
    fprintf(stderr, "FAILURE: usage: fileforge file group");
    exit(1);
  } /* if */
  else {
    /* set source */
    src_file = (char *) malloc(strlen(src_dir) + strlen(argv[1]) + 1);
    strcpy(src_file, src_dir);
    strcat(src_file, argv[1]);

    /* set destination */
    dest_file = (char *) malloc(strlen(dest_dir) + strlen(argv[2]) + 1);
    strcpy(dest_file, dest_dir);
    strcat(dest_file, argv[2]);
    dirname = (char *) malloc(strlen(dest_file) + 1);
    strcpy(dirname, dest_file);
    get_dirname(dirname);

    /* test for legal characters: [a-zA-Z0-9_-.]  */
    /* test for illegal combinations of legal characters: ".." */
    if (!legal_string(src_file)) {
      fprintf(stderr, "FAILURE: illegal characters in source file\n");
      exit(1);
    } /* if */

    if (!legal_string(dest_file)) {
      fprintf(stderr, "FAILURE: illegal characters in destination file\n");
      exit(1);
    } /* if */

    if ((mkdir(dirname, 0775) != 0) && errno != EEXIST) {
      fprintf(stderr, "FAILURE: destination directory could not be created\n");
      exit(1);
    } /* if */

    /* set permissions */
    stat(src_file, &buf);
	// add 'group' read and remove 'other' perms
    chmod(src_file, (((buf.st_mode | S_IRGRP) & ~S_IROTH) & ~S_IWOTH) & ~S_IXOTH);

	/* exec it */
    if (execl(move_path, move_file, src_file, dest_file, (char *)0) == -1) {
      perror("FAILURE");
      exit(1);
    } /* if */
  } /* else */

  printf("OK\n");
  free(dest_file);
  free(dirname);
  free(src_file);
  exit(0);
} /* main */
