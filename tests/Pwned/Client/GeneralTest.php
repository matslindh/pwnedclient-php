<?php
class Pwned_Client_GeneralTest extends Pwned_ClientTestAbstract
{
    /**
     * Test if we can retrieve the list of available games at pwned.
     */
    public function testGetGames()
    {
        $games = $this->client->getGames();
        $this->assertNotEmpty($games);

        $this->assertNotEmpty($games[0]['id']);
    }

    /**
     * Test if we can retrieve the countries available at pwned.
     */
    public function testGetCountries()
    {
        $countries = $this->client->getCountries();

        $this->assertNotEmpty($countries);

        $this->assertNotEmpty($countries[0]['id']);
        $this->assertEquals(count($countries) > 5, true, "Country count is less than five.");
    }

    /**
     * Test if the client is able to ping the API at pwned (to verify that the api is available and the api keys work).
     */
    public function testPing()
    {
        $response = $this->client->ping();

        $this->assertEquals('pong', $response, $this->client->getLastErrorReason());
    }

    /**
     * Test that debugging is disabled by default in the client.
     */
    public function testDefaultDisabledDebugging()
    {
        if (isset($GLOBALS['PWNED_CLIENT_DEBUG']))
        {
            $this->markTestSkipped('Tests is run in debug mode; testing default values in client is not suitable.');
        }

        $this->client->ping();
        $this->assertEmpty($this->client->getDebugValues());
    }

    /**
     * Test that we can turn on debugging and that debugging actually logs values.
     */
    public function testEnableDebugging()
    {
        $this->client->enableDebugging();
        $this->client->ping();
        $this->assertNotEmpty($this->client->getDebugValues());
    }

    /**
     * Test that we can disable debugging after having turned it on.
     */
    public function testDisableDebugging()
    {
        $this->client->enableDebugging();
        $this->client->disableDebugging();

        $this->client->ping();
        $this->assertEmpty($this->client->getDebugValues());
    }

    /**
     * Test that the client returns the correct error when we have an invalid public api key.
     */
    public function testInvalidPublicKey()
    {
        $debuggingState = $this->client->isDebuggingEnabled();

        $this->client->disableDebugging();
        $this->client->setPublicKey('this-is-an-invalid-key');
        $response = $this->client->ping();

        $this->assertEmpty($response);

        $error = $this->client->getLastError();
        $this->assertEquals('invalid_public_key_provided', $error['key']);

        if ($debuggingState)
        {
            $this->client->enableDebugging();
        }
    }

    /**
     * Test that the client returns the correct error when we have an invalid private api key.
     */
    public function testInvalidPrivateKey()
    {
        $debuggingState = $this->client->isDebuggingEnabled();

        $this->client->disableDebugging();
        $this->client->setPrivateKey('this-is-an-invalid-key');
        $response = $this->client->ping();

        $this->assertEmpty($response);

        $error = $this->client->getLastError();
        $this->assertEquals('request_signature_is_invalid', $error['key']);

        if ($debuggingState)
        {
            $this->client->enableDebugging();
        }
    }
}