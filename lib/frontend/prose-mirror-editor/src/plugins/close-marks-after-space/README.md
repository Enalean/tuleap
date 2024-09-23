This plugin prevents the user to be blocked in a mark and can't write the end of their text.
If the user writes in a mark at the end of the line and press "space", if it's a mark that's allowed to be closed, it should close it.

This feature is used to avoid this case :

I can't <sub>123 write the end of my sentence after a mark !</sub>

and have this instead :

I can <sub>123</sub> write the end of my sentence after a mark !

To support other marks, please add it to MARKS_TO_CLOSE.
