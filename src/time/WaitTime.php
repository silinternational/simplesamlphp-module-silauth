<?php
namespace Sil\SilAuth\time;

/**
 * Class to enable assembling a human-friendly description of approximately how
 * long the user must wait before (at least) the given number of seconds have
 * elapsed.
 */
class WaitTime
{
    const UNIT_MINUTE = 'minute';
    const UNIT_SECOND = 'second';
    
    private $friendlyNumber = null;
    private $unit = null;
    
    /**
     * Constructor.
     *
     * NOTE: This will not be precise, as it may round up to have a more
     *       natural-sounding result (e.g. 20 seconds, rather than 17 seconds).
     * 
     * @param int $secondsToWait The number of seconds the user must wait.
     */
    public function __construct($secondsToWait)
    {
        if ($secondsToWait <= 5) {
            $this->friendlyNumber = 5;
            $this->unit = self::UNIT_SECOND;
        } elseif ($secondsToWait <= 30) {
            $this->friendlyNumber = (int) ceil($secondsToWait / 10) * 10;
            $this->unit = self::UNIT_SECOND;
        } else {
            $this->friendlyNumber = (int) ceil($secondsToWait / 60);
            $this->unit = self::UNIT_MINUTE;
        }
    }
    
    public function getFriendlyNumber()
    {
        return $this->friendlyNumber;
    }
    
    public function getUnit()
    {
        return $this->unit;
    }
    
    public function __toString()
    {
        return sprintf(
            '%s %s%s',
            $this->friendlyNumber,
            $this->unit,
            (($this->friendlyNumber === 1) ? '' : 's')
        );
    }
}
