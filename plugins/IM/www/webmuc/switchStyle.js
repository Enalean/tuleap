style = "muckl.css"; // fallback

// look for stylesheet
var cur = parent;
while (cur)
  if (cur.stylesheet) {
    style = cur.stylesheet;
    break;
  } else
    cur = cur.parent;
document.write('<link rel="styleSheet" type="text/css" href="'+style+'">');
