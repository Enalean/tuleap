function pagingInit()
{
	var nav = document.getElementById("nav");
	var tabs = nav.getElementsByTagName("li");
	for(ii = 0; ii < nav.length; ++ii) {
		tabs[ii].className = "close";
		aa = nav[ii].getElementsByTagName("a")
//		aa.onclick = function() { activate(this.href); return false; }
		if ( ii = 0 ) {
			aa.className = "current";
		}
	}
	print("DONE!");
}

function pagingActivate(name)
{
// 	var page
//   if (curHeader.className=="close")
//   {
//     curHeader.className="";
//     curHeader.firstChild.className="";
//   }
//   else if (curHeader.className=="")
//   {
//     curHeader.className="close";
//     curHeader.firstChild.className="close";
//   }
}
