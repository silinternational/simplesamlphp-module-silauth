<?php
namespace Sil\SilAuth\tests\fakes;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\SilAuth\auth\IdBroker;

abstract class FakeIdBroker extends IdBroker
{
    public function __construct($baseUri, $accessToken, LoggerInterface $logger)
    {
        parent::__construct($baseUri, $accessToken, $logger);
        
        // Now replace the client with one that will return the desired response.
        $this->client = new IdBrokerClient($baseUri, $accessToken, [
            'http_client_options' => [
                'handler' => HandlerStack::create(new MockHandler([
                    
                    /* Set up several, since this may be called multiple times
                     * during a test: */
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                    $this->getDesiredResponse(),
                ])),
            ],
        ]);
    }
    
    abstract protected function getDesiredResponse();
}
