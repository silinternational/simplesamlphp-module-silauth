<?php

use Sil\SilAuth\auth\AuthError;
use Sil\SilAuth\models\User;
use Sil\SilAuth\text\Text;

/**
 * This page shows a username/password login form, and passes information from it
 * to the sspmod_silauth_Auth_Source_SilAuth class
 */

// Retrieve the authentication state
if (!array_key_exists('AuthState', $_REQUEST)) {
    throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_silauth_Auth_Source_SilAuth::STAGEID);

$source = SimpleSAML_Auth_Source::getById($state[sspmod_silauth_Auth_Source_SilAuth::AUTHID]);
if ($source === null) {
    throw new Exception('Could not find authentication source with id ' . $state[sspmod_silauth_Auth_Source_SilAuth::AUTHID]);
}

$errorCode = null;
$errorParams = null;
$username = null;
$password = null;

$globalConfig = SimpleSAML_Configuration::getInstance();
$authSourcesConfig = $globalConfig->getConfig('authsources.php');
$silAuthConfig = $authSourcesConfig->getConfigItem('silauth');

$recaptchaSiteKey = $silAuthConfig->getString('recaptcha.siteKey', null);
$recaptchaSecret = $silAuthConfig->getString('recaptcha.secret', null);
$forgotPasswordUrl = $silAuthConfig->getString('link.forgotPassword', null);

$remoteIp = Text::sanitizeInputString(INPUT_SERVER, 'REMOTE_ADDR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        $username = Text::sanitizeInputString(INPUT_POST, 'username');
        $password = Text::sanitizeInputString(INPUT_POST, 'password');
        
        $gRecaptchaResponse = Text::sanitizeInputString(INPUT_POST, 'g-recaptcha-response');
        
        if (User::isCaptchaRequiredFor($username)) {
            AuthError::logWarning(sprintf(
                'Required reCAPTCHA for user %s.',
                var_export($username, true)
            ));
            
            $recaptcha = new \ReCaptcha\ReCaptcha($recaptchaSecret);
            $rcResponse = $recaptcha->verify($gRecaptchaResponse, $remoteIp);
            if ( ! $rcResponse->isSuccess()) {
                AuthError::logError(sprintf(
                    'Failed reCAPTCHA (user %s): %s',
                    var_export($username, true),
                    join(', ', $rcResponse->getErrorCodes())
                ));
                
                /* If they entered a username that has enough failed login
                 * attempts that we need to require captcha, act like they
                 * simply mistyped the password (so that they will re-type
                 * their credentials now that we're using a captcha).  */
                $authError = new AuthError(AuthError::CODE_INVALID_LOGIN);
                throw new SimpleSAML_Error_Error([
                    'WRONGUSERPASS',
                    $authError->getFullSspErrorTag(),
                    $authError->getMessageParams()
                ]);
            }
        }
        
        sspmod_silauth_Auth_Source_SilAuth::handleLogin(
            $authStateId,
            $username,
            $password
        );
    } catch (SimpleSAML_Error_Error $e) {
        /* Login failed. Extract error code and parameters, to display the error. */
        $errorCode = $e->getErrorCode();
        $errorParams = $e->getParameters();
    }
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:loginuserpass.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['username'] = $username;
$t->data['errorcode'] = $errorCode;
$t->data['errorparams'] = $errorParams;
$t->data['forgotPasswordUrl'] = $forgotPasswordUrl;
if (( ! empty($username)) && User::isCaptchaRequiredFor($username)) {
    $t->data['recaptcha.siteKey'] = $recaptchaSiteKey;
}

if (isset($state['SPMetadata'])) {
    $t->data['SPMetadata'] = $state['SPMetadata'];
} else {
    $t->data['SPMetadata'] = null;
}

$t->show();
exit();
