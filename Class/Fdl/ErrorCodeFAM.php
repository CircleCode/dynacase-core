<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Errors code used to checking document's family definition
 * @class ErrorCodeFAM
 * @brief List all error code for BEGIN keyword
 */
class ErrorCodeFAM
{
    /**
     * the inherit family must be recorded before inherit to it
     */
    const FAM0100 = 'inherit family "%s" not found for "%s" family';
    /**
     * a family cannot inherit from itself,. name and fromname must be differtent
     */
    const FAM0101 = 'cannot inherit from itself %s"';
    /**
     * the family inheritance cannot be changed. A migration is needed.
     */
    const FAM0102 = 'inheritance "%s" -> "%s" cannot be changed "%s"';
    /**
     * the inherit family must be a family document
     */
    const FAM0104 = 'inherit family "%s" is not a family  "%s" family';
    /**
     * the title length is less than 255 characters.
     */
    const FAM0200 = 'the title "%s" is too long for "%s" family';
    /**
     *
     */
    const FAM0201 = 'the title "%s" does not have special characters "%s" family';
    /**
     * class file family syntax error
     */
    const FAM0400 = 'syntax error in classfile "%s" in "%s" family : %s';
    /**
     * class file family not found in file system
     */
    const FAM0401 = 'classfile "%s" not found in "%s" family ';
    /**
     * a family must have a logical name
     */
    const FAM0500 = 'family name is required for "%s"';
    /**
     * a family containt only alphanum characters (63 max)
     */
    const FAM0501 = 'syntax error in family name "%s"';
    /**
     * the family name must not reference only a family
     */
    const FAM0502 = 'family name "%s" is already use for "%s" document';
}
