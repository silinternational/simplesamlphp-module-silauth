<?php
namespace Sil\SilAuth\mfa;

class MfaInfo
{
    const EMPLOYEE_ID = 'employee_id';
    const MFA_OPTIONS = 'mfa_options';
    const PROMPT_FOR_MFA = 'prompt_for_mfa';
    
    /**
     * The Employee ID that this MFA info is for.
     *
     * @var string
     */
    private $employeeId;
    
    /**
     * A list of MFA options available to the user.
     *
     * @var array
     */
    private $mfaOptions;
    
    /**
     * Whether to prompt the user for MFA ("yes" or "no").
     *
     * @var string
     */
    private $promptForMfa;
    
    public function __construct(
        string $employeeId,
        string $promptForMfa,
        array $mfaOptions
    ) {
        $this->employeeId = $employeeId;
        $this->promptForMfa = $promptForMfa;
        $this->mfaOptions = $mfaOptions;
    }
    
    /**
     * Create an MfaInfo object from the values in the given array.
     *
     * @param array $info
     * @return MfaInfo
     */
    public static function createFromArray($info)
    {
        return new MfaInfo(
            $info[self::EMPLOYEE_ID],
            $info[self::PROMPT_FOR_MFA],
            $info[self::MFA_OPTIONS] ?? []
        );
    }
    
    public function getEmployeeId()
    {
        return $this->employeeId;
    }
    
    protected static function isNo($text)
    {
        return (strtolower($text) === 'no');
    }
    
    public function shouldPromptForMfa()
    {
        return !self::isNo($this->promptForMfa);
    }
}
