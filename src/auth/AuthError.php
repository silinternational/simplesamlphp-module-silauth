<?php
namespace Sil\SilAuth\auth;

/**
 * An immutable value object class for authentication error information (and
 * related constants and/or static functions).
 */
class AuthError
{
    const CODE_GENERIC_TRY_LATER = 'generic_try_later';
    const CODE_USERNAME_REQUIRED = 'username_required';
    const CODE_PASSWORD_REQUIRED = 'password_required';
    const CODE_INVALID_LOGIN = 'invalid_login';
    const CODE_RATE_LIMIT_SECONDS = 'rate_limit_seconds';
    const CODE_RATE_LIMIT_1_MINUTE = 'rate_limit_1_minute';
    const CODE_RATE_LIMIT_MINUTES = 'rate_limit_minutes';
    
    private $code = null;
    private $message = null;
    private $messageParams = [];
    
    public function __construct($code, $message, $messageParams = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->messageParams = $messageParams;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * Get the error string that should be passed to simpleSAMLphp's translate
     * function for this AuthError. It will correspond to an entry in the
     * appropriate dictionary file provided by this module.
     *
     * @return string Example: '{silauth:error:generic_try_later}'
     */
    protected function getFullSspErrorTag()
    {
        return sprintf(
            '{%s:%s}',
            'silauth:error',
            $this->getCode()
        );
    }
    
    public function getMessageParams()
    {
        return $this->messageParams;
    }
    
    public static function logError($message)
    {
        \Yii::error($message, 'silauth');
    }
    
    public static function logWarning($message)
    {
        \Yii::warning($message, 'silauth');
    }
}
