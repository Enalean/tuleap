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
package com.xerox.xrce.codex.jri.model.tracker;

import com.xerox.xrce.codex.jri.model.wsproxy.Criteria;

/**
 * CxCriteria is the class for artifact search criteria. A CxCriteria is a
 * constraint on a field and respects the format [field] [operator] [value].
 * <ul>
 * For instance:
 * <li>severity=5</li>
 * <li>open_date>='2005-01-01'</li>
 * <li>etc.</li>
 * </ul>
 * 
 */
public class CxCriteria {

    /**
     * Field name of this criteria
     */
    private String fieldName;

    /**
     * The value of this criteria
     */
    private String fieldValue;

    /**
     * The operator of this criteria.
     * <ul>
     * Valid operator are:
     * <li>= (equal)</li>
     * <li>&lt; (less than)</li>
     * <li>&gt; (greater than)</li>
     * <li>&lt;= (less than or equal)</li>
     * <li>&gt;= (greater than or equal)</li>
     * <li>&lt;&gt; (different)</li>
     * </ul>
     */
    private String operator;

    /**
     * Constructor from Criteria Object
     * 
     * @param criteria the Criteria Object
     */
    public CxCriteria(Criteria criteria) {
        this.fieldName = criteria.getField_name();
        this.fieldValue = criteria.getField_value();
        this.operator = criteria.getOperator();
    }

    /**
     * Constructor from data
     * 
     * @param fieldName the name of the field of the criteria
     * @param fieldValue the value of the criteria
     * @param operator the operator of the criteria
     */
    public CxCriteria(String fieldName, String fieldValue, String operator) {
        this.fieldName = fieldName;
        this.fieldValue = fieldValue;
        this.operator = operator;
    }

    /**
     * Return the name of the field of this criteria
     * 
     * @return the name of the field of this criteria
     */
    public String getFieldName() {
        return fieldName;
    }

    /**
     * Return the value of this criteria
     * 
     * @return the value of this criteria
     */
    public String getFieldValue() {
        return fieldValue;
    }

    /**
     * Return the operator of this criteria
     * 
     * @return the operator of this criteria
     */
    public String getOperator() {
        return operator;
    }

}
