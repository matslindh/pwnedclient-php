<?php
class Pwned_ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up a pwned client for internal re-use for each test.
     */
    public function setUp()
    {
        $this->client = new Pwned_Client($GLOBALS['PWNED_API_URL'], $GLOBALS['PWNED_API_PUBLIC_KEY'], $GLOBALS['PWNED_API_PRIVATE_KEY']);
    }
    
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
     * Test if we can create tournaments.
     *
     * @return array An array representing the created competition.
     */
    public function testCreateTournament()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );
        
        $competition = $this->createNewCompetitionForTests($competitionInput);
        
        $this->assertNotEmpty($competition, $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['id'], $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['game']['id']);
        $this->assertEquals($competitionInput['gameId'], $competition['game']['id']);
        $this->assertEquals($competitionInput['name'], $competition['name']);
        $this->assertEquals('tournament', $competition['type']);
        $this->assertEquals(5, $competition['playersOnTeam']);
        $this->assertEquals($competitionInput['template'], $competition['template']);
        $this->assertEquals('ready', $competition['status']);
        $this->assertEquals(16, $competition['teamCount']);
        $this->assertEquals($competitionInput['description'], $competition['description']);
        
        return $competition;
    }
    
    /**
     * Test that we can create a tournament with a group stage.
     */
    public function testCreateTournamentGroupStage()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'doubleelim4',
            'countryId' => 1,
            'groupCount' => 2,
            'groupSize' => 4,
        );

        $competition = $this->createNewCompetitionForTests($competitionInput);
        
        $this->assertNotEmpty($competition, $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['id'], $this->client->getLastErrorReason());
        $this->assertNotEmpty($competition['game']['id']);
        $this->assertEquals($competitionInput['gameId'], $competition['game']['id']);
        $this->assertEquals($competitionInput['name'], $competition['name']);
        $this->assertEquals('tournament', $competition['type']);
        $this->assertEquals(5, $competition['playersOnTeam']);
        $this->assertEquals($competitionInput['template'], $competition['template']);
        $this->assertEquals('ready', $competition['status']);
        $this->assertEquals(8, $competition['teamCount']);
        $this->assertEquals(4, $competition['groupSize']);
        $this->assertEquals(2, $competition['groupCount']);
        
        return $competition;
    }    
    
    /**
     * Test retrieval of all rounds defined for a tournament.
     * 
     * @depends testCreateTournament
     */
    public function testGetRounds($competition)
    {
        $rounds = $this->client->getRounds($competition['type'], $competition['id']);
        $this->assertCount(4, $rounds);
        $this->assertEquals($competition['roundCount'], count($rounds));
        
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
     * Test retrieval of one specific round created for a tournament.
     * 
     * @depends testCreateTournament
     */
    public function testGetRound($competition)
    {
        $round = $this->client->getRound($competition['type'], $competition['id'], 1);
        
        $this->assertCount(8, $round['stages'][0]['matches']);
        $this->assertEquals('PRELIMINARY', $round['identifier']);
    }
    
    /**
     * Test adding of signups to a competition.
     * 
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
     * Test variations of different signups to be added to a tournament.
     * 
     * @depends testCreateTournament
     */
    public function testAddSignupsVariations()
    {
        $competition = $this->createNewCompetitionForTests();
        
        $signups = array(
            array(
                'name' => 'SignupTestVariation One',
                'hasServer' => false,
                'isAccepted' => true,
                'onWaitingList' => true,
                'contact' => 'ContactVariation One',
                'remoteId' => '1234567',
            ),
            array(
                'name' => 'SignupTestVariation Two',
                'hasServer' => true,
                'isAccepted' => false,
                'onWaitingList' => false,
                'contact' => 'ContactVariation Two',
                'remoteId' => '89101113',
            ),
            array(
                'name' => 'SignupTestVariation Three',
                'hasServer' => true,
                'isAccepted' => true,
                'onWaitingList' => false,
                'contact' => 'ContactVariation Three',
                'remoteId' => '89101114',
            ),
            array(
                'name' => 'SignupTestVariation Four',
                'hasServer' => true,
                'isAccepted' => false,
                'onWaitingList' => true,
                'contact' => 'ContactVariation Four',
                'remoteId' => '89101115',
            ),
        );
        
        $addSignups = $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $signupsRegistered = $this->client->getSignups($competition['type'], $competition['id'], 'all');
        
        $this->assertCount(4, $signupsRegistered);
        
        $this->assertNotEmpty($signupsRegistered[0]['id']);
        $this->assertNotEmpty($signupsRegistered[1]['id']);
        $this->assertNotEmpty($signupsRegistered[2]['id']);
        $this->assertNotEmpty($signupsRegistered[3]['id']);
        
        foreach($signups as $idx => $signup)
        {
            foreach($signup as $field => $value)
            {
                $this->assertEquals($value, $signupsRegistered[$idx][$field], $signup['name'] . ': ' . $field . ': ' . $value . ' / ' . $signupsRegistered[$idx][$field]);
            }
        }
        
        return array('competition' => $competition, 'signups' => $signups);
    }
    
    /**
     * Test if we can get the signups that has been registered for a tournament, depending on the type of signup (all, accepted, on waiting list or refused / not accepted).
     * 
     * @depends testAddSignupsVariations
     */
    public function testGetSignupsFetchMode($competitionAndSignups)
    {
        $competition = $competitionAndSignups['competition'];
        
        // all signups
        $signups = $this->client->getSignups($competition['type'], $competition['id'], 'all');
        $this->assertCount(4, $signups);
        
        // accepted and not on waiting list
        $signups = $this->client->getSignups($competition['type'], $competition['id']);
        $this->assertCount(1, $signups);
        
        // accepted on waiting list
        $signups = $this->client->getSignups($competition['type'], $competition['id'], 'waiting');
        $this->assertCount(1, $signups);
        
        // only not accepted
        $signups = $this->client->getSignups($competition['type'], $competition['id'], 'notaccepted');
        $this->assertCount(2, $signups);
        
        return $competition;
    }
    
    /**
     * Test if we can remove a signup from a competition.
     * 
     * @depends testAddSignups
     */
    public function testRemoveSignup($competitionAndSignups)
    {
        $competition = $competitionAndSignups['competition'];
        $signups =  $this->client->getSignups($competition['type'], $competition['id']);;
        $this->assertNotEmpty($signups);
        
        $result = $this->client->removeSignup($competition['type'], $competition['id'], $signups[0]['id']);
        $this->assertEmpty($this->client->getLastError());
        $this->assertTrue($result);
        
        $signupsAfter = $this->client->getSignups($competition['type'], $competition['id']);
        $this->assertEquals(count($signups) - 1, count($signupsAfter), "Number of signups after removing one didn't match the original number - 1");
    }
    
    /**
     * Test if we can replace an existing signup with a new one.
     * 
     * @depends testAddSignups
     */
    public function testReplaceSignup($competitionAndSignups)
    {
        // wether the correct match replacements happen is not a client test, so we'll just test that the method completes OK
        $competition = $competitionAndSignups['competition'];
        $signups = $competitionAndSignups['signups'];
        $this->assertNotEmpty($signups);
        
        $result = $this->client->replaceSignup($competition['type'], $competition['id'], $signups[0]['id'], $signups[1]['id']);
        $this->assertEmpty($this->client->getLastError(), $this->client->getLastError() ? join(',', $this->client->getLastError()) : '');
        
        $this->assertTrue($result);
    }
    
    /**
     * Test if we can retrieve all the available tournament templates.
     */
    public function testGetTournamentTemplates()
    {
        $templates = $this->client->getTournamentTemplates();
        
        $this->assertNotEmpty($templates);
    }
    
    /**
     * Test if the client is able to retrieve information about a competition.
     */
    public function testGetCompetition()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
        );
        
        $competition = $this->createNewCompetitionForTests($competitionInput);
        $competitionFetched = $this->client->getCompetition('tournament', $competition['id']);
        
        $this->assertNotEmpty($competitionFetched, $this->client->getLastErrorReason());
        $this->assertNotEmpty($competitionFetched['id'], $this->client->getLastErrorReason());
        $this->assertNotEmpty($competitionFetched['game']['id']);
        $this->assertEquals($competitionFetched['game']['id'], $competition['game']['id']);
        $this->assertEquals($competitionFetched['name'], $competition['name']);
        $this->assertEquals($competitionFetched['type'], $competition['type']);
        $this->assertEquals($competitionFetched['playersOnTeam'], $competition['playersOnTeam']);
        $this->assertEquals($competitionFetched['template'], $competition['template']);
        $this->assertEquals('ready', $competitionFetched['status']);
        $this->assertEquals($competitionFetched['teamCount'], $competition['teamCount']);
    }
    
    /**
     * Test if we can update the general information about a competition.
     * 
     * @depends testCreateTournament
     */
    public function testUpdateCompetition($competition)
    {
        $updatedInfo = array(
            'name' => 'Foobar Updated Competition #' . uniqid(),
            'gameId' => 1,
            'playersOnTeam' => 2,
            'countryId' => 2,
            'description' => 'foo ' . uniqid(),
            'quickProgress' => true,
        );
        
        $this->client->updateCompetition($competition['type'], $competition['id'], $updatedInfo);
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        
        $this->assertNotEmpty($updatedCompetition, $this->client->getLastErrorReason());
        $this->assertNotEmpty($updatedCompetition['id'], $this->client->getLastErrorReason());
        $this->assertNotEmpty($updatedCompetition['game']['id']);
        $this->assertEquals($updatedInfo['gameId'], $updatedCompetition['game']['id']);
        $this->assertEquals($updatedInfo['name'], $updatedCompetition['name']);
        $this->assertEquals('tournament', $updatedCompetition['type']);
        $this->assertEquals($updatedInfo['playersOnTeam'], $updatedCompetition['playersOnTeam']);
        $this->assertEquals('ready', $updatedCompetition['status']);
        $this->assertEquals(1, $updatedCompetition['quickProgress']);
        $this->assertEquals($updatedInfo['description'], $updatedCompetition['description']);
    }
    
    /**
     * Test if we can change the template of a tournament.
     * 
     * @depends testCreateTournament
     */
    public function testUpdateTournamentTemplate($competition)
    {
        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'template' => 'singleelim8',
        ));
        
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals('singleelim8', $updatedCompetition['template']);
        $this->assertEquals('ready', $updatedCompetition['status']);
        $this->assertEquals(8, $updatedCompetition['teamCount']);
    }
    
    /**
     * Test if we can update template and group size information for a tournament.
     * 
     * @depends testCreateTournament
     */
    public function testUpdateTournamentGroupInformation($competition)
    {
        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'template' => 'singleelim8',
            'groupSize' => 8,
            'groupCount' => 4,
        ));
        
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals('singleelim8', $updatedCompetition['template']);
        $this->assertEquals(8, $updatedCompetition['groupSize']);
        $this->assertEquals(4, $updatedCompetition['groupCount']);
        $this->assertEquals('ready', $updatedCompetition['status']);
        $this->assertEquals(32, $updatedCompetition['teamCount']);
    }
    
    /**
     * Test if we remove a group size setting after setting it
     * 
     * @depends testCreateTournament
     */
    public function testUpdateTournamentRemoveGroupInformation($competition)
    {
        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'template' => 'singleelim8',
            'groupSize' => 8,
            'groupCount' => 4,
        ));
        
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals(8, $updatedCompetition['groupSize']);
        $this->assertEquals(4, $updatedCompetition['groupCount']);
        $this->assertEquals(32, $updatedCompetition['teamCount']);
        
        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'groupSize' => 0,
            'groupCount' => 0,            
        ));
        
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEmpty($updatedCompetition['groupSize']);
        $this->assertEmpty($updatedCompetition['groupCount']);
        $this->assertEquals(8, $updatedCompetition['teamCount']);        
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
        $this->client->setPublicKey('this-is-an-invalid-key');
        $response = $this->client->ping();
        
        $this->assertEmpty($response);
        
        $error = $this->client->getLastError();
        $this->assertEquals('invalid_public_key_provided', $error['key']);
    }
    
    /**
     * Test that the client returns the correct error when we have an invalid private api key.
     */
    public function testInvalidPrivateKey()
    {
        $this->client->setPrivateKey('this-is-an-invalid-key');
        $response = $this->client->ping();
        
        $this->assertEmpty($response);
        
        $error = $this->client->getLastError();
        $this->assertEquals('request_signature_is_invalid', $error['key']);
    }
    
    /**
     * Internal method to create a competition for further tests across test methods.
     * 
     * @param array $competitionInput If we should create a tournament with specific values, supply the information here.
     * @return array An example tournament / competition created for further testing.
     */
    protected function createNewCompetitionForTests($competitionInput = null)
    {
        if (!$competitionInput)
        {
            $competitionInput = array(
                'name' => 'Test Tournament #123',
                'gameId' => 3,
                'playersOnTeam' => 5,
                'template' => 'singleelim16',
                'countryId' => 1,
            );
        }
        
        return $this->client->createTournament($competitionInput);
    }
}