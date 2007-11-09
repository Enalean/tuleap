/**
 * CodeX: Breaking Down the Barriers to Source Code Sharing
 *
 * Copyright (c) Xerox Corporation, CodeX, 2007. All Rights Reserved
 *
 * This file is licensed under the CodeX Component Software License
 *
 * @author Anne Hardyau
 * @author Marc Nazarian
 */

package com.xerox.xrce.codex.jri.utils;

import java.sql.Timestamp;
import java.text.Format;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;

/**
 * CodexDate is the public class to manage Date in Java CodeX applications. It
 * allows you to get Date and Calendar java object from a unix time stamp
 * 
 */
public class CodexDate {

    /**
     * Return the Calendar corresponding to the unixTimeStamp
     * 
     * @param unixTimeStamp the UNIX time stamp to convert
     * @return the Calendar corresponding to the unixTimeStamp
     */
    public static Calendar getCalendar(int unixTimeStamp) {
        long l = unixTimeStamp;
        Timestamp timestamp = new Timestamp(l * 1000);
        Calendar calendar = GregorianCalendar.getInstance();
        calendar.setTimeInMillis(timestamp.getTime());
        return calendar;
    }

    /**
     * Return a string that represent the date given by the unixTimeStamp
     * formatted with the given format
     * 
     * @param unixTimeStamp the UNIX time stamp
     * @param format the format of the returned date
     * @return the formatted date
     */
    public static String getFormattedDate(int unixTimeStamp, String format) {
        if (unixTimeStamp != 0) {
            Calendar calendar = getCalendar(unixTimeStamp);
            Date date = new Date(calendar.getTimeInMillis());
            Format formatter = new SimpleDateFormat(format);
            return formatter.format(date);
        }
        return "";

    }

}
