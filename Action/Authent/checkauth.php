<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * PHP Authentification control
 *
 * @author Anakeen
 * @license http://www.gnu.org/licenses/lgpl-3.0.html GNU Lesser General Public License
 * @package FDL
 * @subpackage CORE
 * @deprecated since HTTP Authentification
 */

function checkauth(Action & $action)
{
    
    include_once ('WHAT/Lib.Common.php');
    include_once ('WHAT/Class.AuthenticatorManager.php');
    include_once ('WHAT/Class.htmlAuthenticator.php');
    include_once ('WHAT/Class.User.php');
    include_once ('WHAT/Class.Log.php');
    
    $status = AuthenticatorManager::checkAccess();
    //error_log("checkauth: AuthenticatorManager::checkAccess() = {$status}");
    switch ($status) {
        case AuthenticatorManager::AccessOk: // it'good, user is authentified, just log the connexion
            AuthenticatorManager::secureLog("success", "welcome", AuthenticatorManager::$auth->provider->parms['type'] . "/" . AuthenticatorManager::$auth->provider->parms['provider'], $_SERVER["REMOTE_ADDR"], AuthenticatorManager::$auth->getAuthUser() , $_SERVER["HTTP_USER_AGENT"]);
            break;

        case AuthenticatorManager::AccessBug:
            // User must change his password
            $action->session->close();
            global $_POST;
            Redirect($action, 'AUTHENT', 'ERRNO_BUG_639');
            exit(0);
            break;

        default:
            AuthenticatorManager::$auth->askAuthentication(array(
                'error' => $status,
                'auth_user' => $_POST['auth_user']
            ));
            exit(0);
    }
    
    $fromuri = AuthenticatorManager::$session->read('fromuri');
    if (($fromuri == "") || (preg_match('/app=AUTHENT/', $fromuri))) {
        $fromuri = ".";
    }
    $lang = array();;
    include_once ('CORE/lang.php');
    $core_lang = getHttpVars('CORE_LANG');
    if ($core_lang != "" && array_key_exists($core_lang, $lang)) {
        //     error_log(__CLASS__."::".__FUNCTION__." "."Registering vaviable CORE_LANG = '".$core_lang."' in session_auth");
        AuthenticatorManager::$session->register('CORE_LANG', $core_lang);
    }
    //   error_log(__CLASS__."::".__FUNCTION__." ".'Redirect Location: '.$fromuri);
    // clean $fromuri
    $fromuri = preg_replace('!//+!', '/', $fromuri);
    $fromuri = preg_replace('!&&+!', '&', $fromuri);
    // Redirect to initial page
    header('Location: ' . $fromuri);
    exit(0);
}
?>
