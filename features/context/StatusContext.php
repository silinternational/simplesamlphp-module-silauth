<?php

namespace Sil\SilAuth\features\context;

use Behat\Behat\Context\Context;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class StatusContext implements Context
{
    private $responseCode = null;
    private $responseText = null;

    /**
     * @When I check the status of this module
     * @throws GuzzleException
     */
    public function iCheckTheStatusOfThisModule()
    {
        $client = new Client();
        $response = $client->get('http://web/module.php/silauth/status.php');
        $this->responseCode = $response->getStatusCode();
        $this->responseText = $response->getBody()->getContents();
    }

    /**
     * @Then I should get back a(n) :responseText with an HTTP status code of :statusCode
     */
    public function iShouldGetBackAWithAnHttpStatusCodeOf($responseText, $statusCode)
    {
        Assert::same($this->responseText, $responseText);
        Assert::eq($this->responseCode, $statusCode);
    }
}
