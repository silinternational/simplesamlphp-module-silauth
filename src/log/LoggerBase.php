<?php
namespace Sil\SilAuth\log;

/**
 * A base class that implements the interpolate function, to reduce duplication
 * in logger classes provided by this project.
 */
abstract class LoggerBase extends \Psr\Log\AbstractLogger
{
    /**
     * Interpolate context values into the message placeholders.
     * 
     * This is based heavily on the example implementation here:
     * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
     * 
     * @param string $message The message (potentially with placeholders).
     * @param array $context The array of values to insert into the
     *     corresponding placeholders.
     * @return string The resulting string.
     */
    protected function interpolate($message, array $context = [])
    {
        // Build a replacement array with braces around the context keys.
        $replace = [];
        foreach ($context as $key => $value) {
            // Check that the value can be cast to string.
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        // Interpolate replacement values into the message.
        $result = strtr($message, $replace);
        
        if (is_string($result)) {
            return $result;
        }
        
        /* If something went wrong, return the original message (with a
         * warning).  */
        return sprintf(
            '%s (WARNING: Unable to interpolate the context values into the message. %s).',
            $message,
            var_export($replace, true)
        );
    }
}
