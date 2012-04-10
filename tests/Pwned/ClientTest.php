<?php
class Pwned_ClientTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->client = new Pwned_Client($GLOBALS['PWNED_API_URL'], $GLOBALS['PWNED_API_PUBLIC_KEY'], $GLOBALS['PWNED_API_PRIVATE_KEY']);
    }
    
    public function testGetGames()
    {
        $games = $this->client->getGames();
        $this->assertNotEmpty($games);
        
        $this->assertNotEmpty($games[0]['id']);
    }
    
    public function testGetCountries()
    {
        $countries = $this->client->getCountries();
        
        $this->assertNotEmpty($countries);
        
        $this->assertNotEmpty($countries[0]['id']);
        $this->assertEquals(count($countries) > 5, true, "Country count is less than five.");
    }
    
    public function testCreateTournament()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
        );
        
        $this->client->enableDebugging();
        
        $competition = $this->client->createTournament($competitionInput);
        
        $this->assertNotEmpty($competition, $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['id'], $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['game']['id']);
        $this->assertEquals($competition['game']['id'], $competitionInput['gameId']);
        $this->assertEquals($competition['name'], $competitionInput['name']);
        $this->assertEquals($competition['type'], 'tournament');
        $this->assertEquals($competition['playersOnTeam'], 5);
        $this->assertEquals($competition['template'], $competitionInput['template']);
        $this->assertEquals($competition['status'], 'ready');
        $this->assertEquals($competition['teamCount'], 16);
        
        return $competition;
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testGetRounds($competition)
    {
        $rounds = $this->client->getRounds($competition['type'], $competition['id']);
        $this->assertCount(4, $rounds);
        
        // add check for actual content of rounds here..
        $this->assertCount(8, $rounds[0]['stages'][0]['matches']);
        $this->assertCount(4, $rounds[1]['stages'][0]['matches']);
        $this->assertCount(2, $rounds[2]['stages'][0]['matches']);
        $this->assertCount(1, $rounds[3]['stages'][0]['matches']);
        
        $this->assertEquals('PRELIMINARY', $rounds[0]['identifier']);
        $this->assertEquals('QUARTERFINALS', $rounds[1]['identifier']);        
        $this->assertEquals('SEMIFINALS', $rounds[2]['identifier']);        
        $this->assertEquals('FINAL', $rounds[3]['identifier']);
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testGetRound($competition)
    {
        $round = $this->client->getRound($competition['type'], $competition['id'], 1);
        
        $this->assertCount(8, $round['stages'][0]['matches']);
        $this->assertEquals('PRELIMINARY', $round['identifier']);
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testAddSignups($competition)
    {
        $signupOne = array(
            'name' => 'SignupTest One',
            'hasServer' => false,
            'isAccepted' => true,
            'onWaitingList' => false,
            'contact' => 'Contact One',
            'remoteId' => '1234567',
        );
        
        $signupTwo = array(
            'name' => 'SignupTest Two',
            'hasServer' => true,
            'isAccepted' => true,
            'onWaitingList' => false,
            'contact' => 'Contact Two',
            'remoteId' => '89101112',
        );
        
        $addSignups = $this->client->addSignups($competition['type'], $competition['id'], array($signupOne, $signupTwo));
        
        $signups = $this->client->getSignups($competition['type'], $competition['id']);
        
        $this->assertCount(2, $signups);
        
        $this->assertNotEmpty($signups[0]['id']);
        $this->assertNotEmpty($signups[1]['id']);
        
        foreach(array($signupOne, $signupTwo) as $idx => $signup)
        {
            foreach($signup as $field => $value)
            {
                $this->assertEquals($signups[$idx][$field], $value);
            }
        }
        
        return array('competition' => $competition, 'signups' => $signups);
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testAddSignupsToWaitingList()
    {
        
    }
    
    /**
     * @depends testAddSignups
     */
    public function testRemoveSignup($competitionAndSignups)
    {
        
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testGetMatch()
    {
        
    }
    
    /**
     * @depends testCreateTournament
     */
    public function testUpdateMatch()
    {
        
    }
    
    public function testPing()
    {
        $response = $this->client->ping();
        
        $this->assertEquals('pong', $response, $this->client->getLastErrorReason());
    }

    public function testDefaultDisabledDebugging()
    {
        $this->client->ping();
        $this->assertEmpty($this->client->getDebugValues());
    }
    
    public function testEnableDebugging()
    {
        $this->client->enableDebugging();
        $this->client->ping();
        $this->assertNotEmpty($this->client->getDebugValues());
    }
    
    public function testDisableDebugging()
    {
        $this->client->enableDebugging();
        $this->client->disableDebugging();
        
        $this->client->ping();
        $this->assertEmpty($this->client->getDebugValues());
    }
    
    public function testInvalidPublicKey()
    {
        $this->client->setPublicKey('this-is-an-invalid-key');
        $response = $this->client->ping();
        
        $this->assertEmpty($response);
        
        $error = $this->client->getLastError();
        $this->assertEquals('invalid_public_key_provided', $error['key']);
    }
    
    public function testInvalidPrivateKey()
    {
        $this->client->setPrivateKey('this-is-an-invalid-key');
        $response = $this->client->ping();
        
        $this->assertEmpty($response);
        
        $error = $this->client->getLastError();
        $this->assertEquals('request_signature_is_invalid', $error['key']);
    }
}