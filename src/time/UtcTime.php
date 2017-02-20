<?php
namespace Sil\SilAuth\time;

class UtcTime
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    
    private $utc;
    private $dateTime;
    
    /**
     * Create an object representing a date/time in Coordinated Universal Time
     * (UTC).
     * 
     * @param string $dateTimeString (Optional:) A string describing some
     *     date/time. If not given, 'now' will be used. For more information,
     *     see <http://php.net/manual/en/datetime.formats.php>.
     */
    public function __construct(string $dateTimeString = 'now')
    {
        $this->utc = new \DateTimeZone('UTC');
        $this->dateTime = new \DateTime($dateTimeString, $this->utc);
    }
    
    public function __toString()
    {
        return $this->dateTime->format(self::DATE_TIME_FORMAT);
    }
    
    /**
     * Convert the given date/time description to a formatted date/time string
     * in the UTC time zone.
     * 
     * @param string $dateTimeString (Optional:) The date/time to use. If not
     *     given, 'now' will be used.
     * @return string
     */
    public static function format(string $dateTimeString = 'now')
    {
        return (string)(new UtcTime($dateTimeString));
    }
    
    /**
     * Get the number of seconds we have to go back to get from this UTC time to
     * the given UTC time. A positive number will be returned if the given UTC
     * time occurred before this UTC time. If they are the same, zero will be
     * returned. Otherwise, a negative number will be returned.
     *
     * @param \Sil\SilAuth\time\UtcTime $otherUtcTime The other UTC time
     *     (presumably in the past, though not necessarily).
     * @return int The number of seconds
     */
    public function getSecondsSince(UtcTime $otherUtcTime)
    {
        return $this->getTimestamp() - $otherUtcTime->getTimestamp();
    }
    
    
    public function getSecondsUntil(UtcTime $otherUtcTime)
    {
        return $otherUtcTime->getTimestamp() - $this->getTimestamp();
    }
    
    public function getTimestamp()
    {
        return $this->dateTime->getTimestamp();
    }
}
