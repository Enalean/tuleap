// Title: Timestamp picker
// Description: See the demo at url
// URL: http://www.geocities.com/tspicker/
// Version: 1.0.a (Date selector only) reworked by Richard Perry
// Date: 12-12-2001 (mm-dd-yyyy)
// Version 1.0.b reworked by Eddie May. Added time display and made compliant with Netscape 6.2.
// Date 22/01/2002 (dd-mm-yyyy).
// Author: Denis Gritcyuk <denis@softcomplex.com>; <tspicker@yahoo.com>
// Notes: Permission given to use this script in any kind of applications if
//    header lines are left unchanged. Feel free to contact the author
//    for feature requests and/or donations
//
// Modified by Laurent Julliard for CodeX project
// $Id$

function show_calendar(str_target, str_datetime, css_theme_file, img_theme_path) {
        var arr_months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
                "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        var week_days = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        var n_weekstart = 1; // day week starts from (normally 0 or 1)

	// If no date/time given then default to today at 00:00
	if (str_datetime == null || str_datetime =="") {
	//	var dt_datetime = str2dt(dt2dtstr(new Date()) + "00:00");
		var dt_datetime = new Date();
	} else {
           	var dt_datetime = str2dt(str_datetime);
	}

	//var dt_datetime = (str_datetime == null || str_datetime =="" ?  new Date() : str2dt(str_datetime));
		
	var dt_prev_month = new Date(dt_datetime);
	dt_prev_month.setMonth(dt_datetime.getMonth()-1);
	if (dt_datetime.getMonth()%12 != (dt_prev_month.getMonth()+1)%12) {
		dt_prev_month.setMonth(dt_datetime.getMonth());
		dt_prev_month.setDate(0);
	}
	var dt_next_month = new Date(dt_datetime);
	dt_next_month.setMonth(dt_datetime.getMonth()+1);
	if ((dt_datetime.getMonth() + 1)%12 != dt_next_month.getMonth()%12)
		dt_next_month.setDate(0);
	

        var dt_prev_year = new Date(dt_datetime);
        //dt_prev_year.setYear(dt_datetime.getYear()-1);
		dt_prev_year.setYear(dt_datetime.getFullYear()-1);
       
        var dt_next_year = new Date(dt_datetime);
        //dt_next_year.setYear(dt_datetime.getYear()+1);
		dt_next_year.setYear(dt_datetime.getFullYear()+1);
        
        var dt_firstday = new Date(dt_datetime);
        dt_firstday.setDate(1);
        dt_firstday.setDate(1-(7+dt_firstday.getDay()-n_weekstart)%7);
        var dt_lastday = new Date(dt_next_month);
        dt_lastday.setDate(0);

        // html generation (feel free to tune it for your particular application)
        // print calendar header
      var str_buffer = new String (
                "<html>\n"+
                "<head>\n"+
                "        <title>Calendar</title>\n"+
                "<link rel=\"stylesheet\" type=\"text/css\" href=\""+css_theme_file+"\">\n"+
                "</head>\n"+
                "<body>\n"+
                "<table class=\"clsOTable\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n"+
                "<tr><td class=\"calendar_month\">\n"+
                "<table cellspacing=\"1\" cellpadding=\"3\" border=\"0\" width=\"100%\">\n"+
                "<tr>\n"+
                "        <td class=\"calendar_month\"><a href=\"javascript:window.opener.show_calendar('"+
                //str_target+"', '"+dt2dtstr(dt_prev_year)+"'+document.cal.time.value);\">"+
                str_target+"', '"+dt2dtstr(dt_prev_year)+"','"+css_theme_file+"','"+img_theme_path+"');\">"+
		"<img src=\""+img_theme_path+"/calendar/prev_year.png\" width=\"16\" height=\"16\" border=\"0\""+
                " alt=\"previous year\"></a></td>\n"+
                "        <td class=\"calendar_month\"><a href=\"javascript:window.opener.show_calendar('"+
                //str_target+"', '"+ dt2dtstr(dt_prev_month)+"'+document.cal.time.value);\">"+
		str_target+"', '"+ dt2dtstr(dt_prev_month)+"','"+css_theme_file+"','"+img_theme_path+"');\">"+
                "<img src=\""+img_theme_path+"/calendar/prev.png\" width=\"16\" height=\"16\" border=\"0\""+
                " alt=\"previous month\"></a></td>\n"+

                "        <td class=\"calendar_month\" colspan=\"3\" align=\"center\">"+
                "<span class=\"calendar_font_month\">"
                +arr_months[dt_datetime.getMonth()]+" "+dt_datetime.getFullYear()+"</span></td>\n"+

                "        <td class=\"calendar_month\" align=\"right\"><a href=\"javascript:window.opener.show_calendar('"
                //+str_target+"', '"+dt2dtstr(dt_next_month)+"'+document.cal.time.value);\">"+
                +str_target+"', '"+dt2dtstr(dt_next_month)+"','"+css_theme_file+"','"+img_theme_path+"');\">"+
                "<img src=\""+img_theme_path+"/calendar/next.png\" width=\"16\" height=\"16\" border=\"0\""+
                " alt=\"next month\"></a></td>\n"+
                "        <td class=\"calendar_month\" align=\"right\"><a href=\"javascript:window.opener.show_calendar('"
                //+str_target+"', '"+dt2dtstr(dt_next_year)+"'+document.cal.time.value);\">"+
		+str_target+"', '"+dt2dtstr(dt_next_year)+"','"+css_theme_file+"','"+img_theme_path+"');\">"+
                "<img src=\""+img_theme_path+"/calendar/next_year.png\" width=\"16\" height=\"16\" border=\"0\""+
                " alt=\"next year\"></a></td>\n"+
                "</tr>\n");
        var dt_current_day = new Date(dt_firstday);
        // print weekdays titles
        str_buffer += "<tr>\n";
        for (var n=0; n<7; n++)
                str_buffer += "        <td class=\"calendar_day\">"+
                "<span  class=\"calendar_font_day\">"+
                week_days[(n_weekstart+n)%7]+"</span></td>\n";
        // print calendar table
        str_buffer += "</tr>\n";
        while (dt_current_day.getMonth() == dt_datetime.getMonth() ||
                dt_current_day.getMonth() == dt_firstday.getMonth()) {
                // print row header
                str_buffer += "<tr>\n";
                for (var n_current_wday=0; n_current_wday<7; n_current_wday++) {
                                if (dt_current_day.getDate() == dt_datetime.getDate() &&
                                        dt_current_day.getMonth() == dt_datetime.getMonth())
                                        // print current date
                                        str_buffer += "        <td class=\"calendar_currentday\" align=\"right\">";
                                else if (dt_current_day.getDay() == 0 || dt_current_day.getDay() == 6)
                                        // weekend days
                                        str_buffer += "        <td class=\"calendar_nextmonth\" align=\"right\">";
                                else
                                        // print working days of current month
                                        str_buffer += "        <td class=\"calendar_daymonth\" align=\"right\">";

                                if (dt_current_day.getMonth() == dt_datetime.getMonth())
                                        // print days of current month
                                        str_buffer += "<a href=\"javascript:window.opener."+str_target+
					    //".value='"+dt2dtstr(dt_current_day)+"'+document.cal.time.value; window.close();\">"+
                                        ".value='"+dt2dtstr(dt_current_day)+"'; window.close();\">"+
                                        "<span class=\"calendar_font\">";
                                else
                                        // print days of other months
                                        str_buffer += "<a href=\"javascript:window.opener."+str_target+
					    //".value='"+dt2dtstr(dt_current_day)+"'+document.cal.time.value; window.close();\">"+
                                        ".value='"+dt2dtstr(dt_current_day)+"'; window.close();\">"+
                                        "<span class=\"calendar_font_othermonth\">";
                                str_buffer += dt_current_day.getDate()+"</span></a></td>\n";
                                dt_current_day.setDate(dt_current_day.getDate()+1);
                }
                // print row footer
                str_buffer += "</tr>\n";
        }
        // print calendar footer

          str_buffer +=
	         "</table>\n" +
                  "</tr>\n</td>\n</table>\n" +
                  "</body>\n" +
                  "</html>\n";


        var vWinCal = window.open("", "Calendar",
                "width=200,height=187,status=no,resizable=yes,top=200,left=200");
        vWinCal.opener = self;
        vWinCal.focus();
        var calc_doc = vWinCal.document;
        calc_doc.write (str_buffer);
        calc_doc.close();
		
}
// datetime parsing and formatting routines. modify them if you wish other datetime format
function str2dt(str_datetime) {
   //var re_date = /^(\d+)\-(\d+)\-(\d+)\s+(\d+)\:(\d+)$/;
   var re_date = /^(\d+)\-(\d+)\-(\d+)\s*$/;
	if (!re_date.exec(str_datetime))
		return alert("Invalid Datetime format: "+ str_datetime);
	return (new Date (RegExp.$1, RegExp.$2-1, RegExp.$3, RegExp.$4, RegExp.$5,0));
}

function dt2dtstr(dt_datetime) {
	return (new String (
			dt_datetime.getFullYear()+"-"+(dt_datetime.getMonth()+1)+"-"+dt_datetime.getDate()+" "));
}

function dt2tmstr(dt_datetime) {
	return (new String (
			dt_datetime.getHours()+":"+dt_datetime.getMinutes()));
}

