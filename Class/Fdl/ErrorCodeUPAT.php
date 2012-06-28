<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Error codes used to update set od documents
 * @class ErrorCodeUPAT
 * @brief List all error code for global update attribute
 * @see ErrorCode
 * @see UpdateAttribute
 */
class ErrorCodeUPAT
{
    /**
     * attribut syntax not correct
     * the attribute argument is not a valid attribute reference
     */
    const UPAT0001 = 'Attribute syntax error "%s". Update is aborted';
    /**
     * the search use to create document list must use SearchDoc::setObjectReturn(true)
     */
    const UPAT0002 = 'Document List Search must be declared with return Object';
    /**
     * cannot access to status file
     * @see UpdateAttributeStatus
     */
    const UPAT0003 = 'Status file "%s" not exist';
    /**
     * the attribute parameter set is not correct reference
     * @see UpdateAttribute
     */
    const UPAT0004 = 'cannot use UpdateAttribute : attribute "%s" not found for "%s" family';
    /**
     * the document list must refer to a search which use correct family filter
     * @see UpdateAttribute
     */
    const UPAT0005 = 'cannot use UpdateAttribute :  family "%s" not found';
    /**
     * the document list must refer to a search which use family filter
     * @see UpdateAttribute
     */
    const UPAT0006 = 'cannot use UpdateAttribute :  no family filter';
    /**
     * can use addValue only for multiple attribute
     * @see UpdateAttribute::addValue
     */
    const UPAT0007 = 'cannot use addValue :  the attribute "%s" (%s) must be declared as multiple';
    /**
     * can use array argument for addValue only for multiple attribute which are in array and must not have multiple=yes option
     * @see UpdateAttribute::addValue
     */
    const UPAT0008 = 'cannot add an array in addValue when attribute is not in an array :  the attribute "%s"(%s)  must not have multiple=yes option';
}
