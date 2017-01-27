<?php
namespace Sil\SilAuth\tests\unit\csrf;

/**
 * Class to mimic the bare basics of the SimpleSAML_Session class in order to
 * allow good testing of the CsrfProtector class.
 */
class FakeSession extends \SimpleSAML_Session
{
    private $dataStore;
    
    private function __construct($transient = false)
    {
        $this->dataStore = [];
    }
    
    /**
     * @param string $type
     * @param string|null $id
     * @return mixed
     */
    public function getData($type, $id)
    {
        return $this->dataStore[$type][$id] ?? null;
    }
    
    public static function getSession($sessionId = null)
    {
        return new self();
    }
    
    public function setData($type, $id, $data, $timeout = null)
    {
        // Make sure an array exists for that type of data.
        $this->dataStore[$type] = $this->dataStore[$type] ?? [];
        
        // Store the given data.
        $this->dataStore[$type][$id] = $data;
    }
}
