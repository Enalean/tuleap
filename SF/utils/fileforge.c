/*
 * Small Tool to move files from the FTP incoming dir
 * to the project ftp space.  Runs +suid.
 *
 * $Id$
 */
#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <fcntl.h>
#include <unistd.h>
#include <errno.h>

int legal_string (char* test_string) {

  /* test for legal characters:
     -./0-9  45-57
     A-Z     65-90
     a-z     97-122
   */

  int i;

  for (i = 0; i < strlen(test_string); i++) {
    if ( (test_string[i] < 43) || (test_string[i] == 44) ||
	 ((test_string[i] > 57) && (test_string[i] < 65)) ||
	 ((test_string[i] != 95) && (test_string[i] > 90) && (test_string[i] < 97)) ||
	 (test_string[i] > 122) ) {
      printf("%c", test_string[i]);
      return 0;
    } /* if */
  } /* for */

  /* test for illegal combinations of legal characters: ".." */
  if (strstr(test_string, "..")) {
    return 0;
  } /* if */

  return 1;
} /* legal_string */

int main (int argc, char** argv) {

  /* edit me */
  char* src_dir   = "/nfs/garbage/incoming/";
  char* dest_dir  = "/nfs/garbage/sourceforge/";

  /* don't edit me (unless mv isn't in /bin) */
  char* move_path = "/bin/mv";
  char* move_file = "mv";
  char* dest_file;
  char* src_file;

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

    if ((mkdir(dest_file, 0775) != 0) && errno != EEXIST) {
      fprintf(stderr, "FAILURE: destination directory could not be created\n");
      exit(1);
    } /* if */

    /* exec it */
    if (execl(move_path, move_file, src_file, dest_file, (char *)0) == -1) {
      perror("FAILURE");
      exit(1);
    } /* if */
  } /* else */

  printf("OK\n");
  free(dest_file);
  free(src_file);
  exit(0);
} /* main */
