<?php
class Pwned_Client_CompetitionTest extends Pwned_ClientTestAbstract
{
    /**
     * Test retrieval of all rounds defined for a tournament.
     */
    public function testGetRounds()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );
        
        $competition = $this->createNewTournamentForTests($competitionInput);
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
     */
    public function testGetRound()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );
        
        $competition = $this->createNewTournamentForTests($competitionInput);
        $round = $this->client->getRound($competition['type'], $competition['id'], 1);
        
        $this->assertCount(8, $round['stages'][0]['matches']);
        $this->assertEquals('PRELIMINARY', $round['identifier']);
    }
    
    /**
     * Test adding of signups to a competition.
     */
    public function testAddSignups()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );
        
        $competition = $this->createNewTournamentForTests($competitionInput);        
        
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
     */
    public function testAddSignupsVariations()
    {
        $competition = $this->createNewTournamentForTests();
        
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
     * Test retrieval of information about one particular match.
     */
    public function testGetMatch()
    {
        $competition = $this->createNewTournamentForTests(array(
            'template' => 'singleelim4',
        ));
        
        $this->client->addSignups($competition['type'], $competition['id'], $this->generateRandomSignups(4));
        
        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);
        $rounds = $this->client->getRounds($competition['type'], $competition['id']);
        $called = false;
        
        foreach ($rounds[0]['stages'] as $stage)
        {
            foreach ($stage['matches'] as $match)
            {
                $fetchedMatch = $this->client->getMatch($competition['type'], $competition['id'], $match['id']);
                $called = true;
                
                $this->assertNotEmpty($fetchedMatch);
                $this->assertEquals($match['id'], $fetchedMatch['id']);
            }
        }
        
        $this->assertTrue($called);
    }
    
    /**
     * Test storage of match results.
     */
    public function testUpdateMatch()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'template' => 'singleelim4',
            'playersOnTeam' => 1,
            'gameId' => 1,
            'countryId' => 1,
        ));
        
        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        
        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);
        
        $rounds = $this->client->getRounds($competition['type'], $competition['id']);
        $scores = array();
        
        foreach ($rounds[0]['stages'] as $stage)
        {
            foreach ($stage['matches'] as $match)
            {
                $scores[$match['id']] = array(
                    'score' => rand(1, 50),
                    'scoreOpponent' => rand(1, 50),
                );
                
                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], $scores[$match['id']]);
            }
        }
        
        $rounds = $this->client->getRounds($competition['type'], $competition['id']);
        
        foreach ($rounds[0]['stages'] as $stage)
        {
            foreach ($stage['matches'] as $match)
            {
                $this->assertEquals($scores[$match['id']]['score'], $match['score']);
                $this->assertEquals($scores[$match['id']]['scoreOpponent'], $match['scoreOpponent']);
            }
        }
    }
    
    
}