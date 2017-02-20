<?php
namespace Sil\SilAuth\http;

use Sil\SilAuth\text\Text;

class Request
{
    private $trustedIpAddresses;
    
    public function __construct(array $trustedIpAddresses = [])
    {
        $this->trustedIpAddresses = $trustedIpAddresses;
    }
    
    public function getCaptchaResponse()
    {
        return self::sanitizeInputString(INPUT_POST, 'g-recaptcha-response');
    }
    
    /**
     * Get the list of IP addresses from the current HTTP request. They will be
     * in order such that the last IP address in the list belongs to the device
     * that most recently handled the request (probably our load balancer). The
     * IP address first in the list is both (A) more likely to be the user's
     * actual IP address and (B) most likely to be forged.
     *
     * @return string[] A list of IP addresses.
     */
    protected function getIpAddresses()
    {
        $ipAddresses = [];
        
        // First add the X-Forwarded-For IP addresses.
        $xForwardedFor = self::sanitizeInputString(
            INPUT_SERVER,
            'HTTP_X_FORWARDED_FOR'
        );
        foreach (explode(',', $xForwardedFor) as $xffIpAddress) {
            $trimmedIpAddress = trim($xffIpAddress);
            if (self::isValidIpAddress($trimmedIpAddress)) {
                $ipAddresses[] = $trimmedIpAddress;
            }
        }
        
        /* Finally, add the REMOTE_ADDR server value, since it belongs to the
         * device that directly passed this request to our application.  */
        $remoteAddr = self::sanitizeInputString(INPUT_SERVER, 'REMOTE_ADDR');
        if (self::isValidIpAddress($remoteAddr)) {
            $ipAddresses[] = $remoteAddr;
        }
        
        return $ipAddresses;
    }
    
    /**
     * Get the IP address that this request most likely originated from.
     *
     * @return string|null An IP address, or null if none was available.
     */
    public function getMostLikelyIpAddress()
    {
        $untrustedIpAddresses = $this->getUntrustedIpAddresses();
        
        /* Given the way X-Forwarded-For (and other?) headers work, the last
         * entry in the list was the IP address of the system closest to our
         * application. Once we filter out trusted IP addresses (such as that of
         * our load balancer, etc.), the last remaining IP address in the list
         * is probably the one we care about:
         * 
         * "Since it is easy to forge an X-Forwarded-For field the given 
         *  information should be used with care. The last IP address is always
         *  the IP address that connects to the last proxy, which means it is
         *  the most reliable source of information."
         * - https://en.wikipedia.org/wiki/X-Forwarded-For
         */
        $userIpAddress = last($untrustedIpAddresses);
        
        /* Make sure we actually ended up with an IP address (not FALSE, which
         * `last()` would return if there were no entries).  */
        return self::isValidIpAddress($userIpAddress) ? $userIpAddress : null;
    }
    
    public function getUntrustedIpAddresses()
    {
        $untrustedIpAddresses = [];
        foreach ($this->getIpAddresses() as $ipAddress) {
            
            /* @todo Should we make this comparison case-insensitive? */
            if ( ! in_array($ipAddress, $this->trustedIpAddresses)) {
                $untrustedIpAddresses[] = $ipAddress;
            }
        }
        return $untrustedIpAddresses;
    }

    /**
     * Check that a given string is a valid IP address
     *
     * @param  string  $ip
     * @return boolean
     */
    public static function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        return (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false);
    }
    
    /**
     * Retrieve input data (see `filter_input(...)` for details) and sanitize
     * it (see Text::sanitizeString).
     * 
     * @param int $inputType Example: INPUT_POST
     * @param string $variableName Example: 'username'
     * @return string
     */
    public static function sanitizeInputString(int $inputType, string $variableName)
    {
        return Text::sanitizeString(filter_input($inputType, $variableName));
    }
}
