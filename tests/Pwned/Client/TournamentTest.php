<?php
class Pwned_Client_TournamentTest extends Pwned_ClientTestAbstract
{
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
        
        $competition = $this->createNewTournamentForTests($competitionInput);
        
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

        $competition = $this->createNewTournamentForTests($competitionInput);
        
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
    public function testGetTournament()
    {
        $competitionInput = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
        );
        
        $competition = $this->createNewTournamentForTests($competitionInput);
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
    public function testUpdateTournament($competition)
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
     * Test that the client handles 'null' values correctly when resetting group information
     * 
     * @depends testCreateTournament
     */
    public function testUpdateTournamentRemoveGroupInformationWithNulls($competition)
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
            'groupSize' => null,
            'groupCount' => null,            
        ));
        
        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEmpty($updatedCompetition['groupSize']);
        $this->assertEmpty($updatedCompetition['groupCount']);
        $this->assertEquals(8, $updatedCompetition['teamCount']);        
    }
    
    /**
     * Test if explicitly asking for walkovers to be moved to next round works.
     */
    public function testMoveWalkoversToNextRound()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'template' => 'singleelim8',
            'playersOnTeam' => 1,
            'gameId' => 1,
            'countryId' => 1,
            'quickProgress' => false,
        ));
        
        $signups = $this->generateRandomSignups(6);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);
        
        $round = $this->client->getRound($competition['type'], $competition['id'], 1);
        $matches = $round['stages'][0]['matches'];
        
        $this->assertCount(4, $matches);

        $valuesToMove = array();
        
        foreach ($matches as $match)
        {
            if (!$match['signup'])
            {
                $valuesToMove[$match['signupOpponent']['id']] = true;
            }
            else if (!$match['signupOpponent'])
            {
                $valuesToMove[$match['signup']['id']] = true;
            }
        }
        
        $this->assertCount(2, $valuesToMove);
        
        $response = $this->client->moveTournamentWalkoversToNextRound($competition['type'], $competition['id']);
        $round = $this->client->getRound($competition['type'], $competition['id'], 2);
        
        foreach ($round['stages'][0]['matches'] as $match)
        {
            if ($match['signup'])
            {
                $this->assertNotEmpty($valuesToMove[$match['signup']['id']]);
                unset($valuesToMove[$match['signup']['id']]);
            }
            
            if ($match['signupOpponent'])
            {
                $this->assertNotEmpty($valuesToMove[$match['signupOpponent']['id']]);
                unset($valuesToMove[$match['signupOpponent']['id']]);
            }
        }
        
        // make sure that both signups were moved
        $this->assertCount(0, $valuesToMove);
    }
    
    
}