<?php
class Pwned_Client_LeagueTest extends Pwned_ClientTestAbstract
{
    /**
     * Test if we're able to create leagues
     */
    public function testLeagueCreate()
    {
        $name = uniqid('League ');
        
        $league = $this->createNewLeagueForTests(array(
            'name' => $name,
            'description' => 'Description here!',
            'leagueType' => 'league',
        ));
        
        $this->assertEquals($name, $league['name']);
        $this->assertEquals('Description here!', $league['description']);
        $this->assertEquals('league', $league['leagueType']);
    }
    
    /**
     * Test if we're able to add signups to a league
     */
    public function testAddSignupsToLeague()
    {
        $league = $this->createNewLeagueForTests(array(
            'name' => 'League ' . uniqid(),
            'teamCount' => 8,
        ));
        
        $signups = $this->generateRandomSignups(8);
        $this->client->addSignups($league['type'], $league['id'], $signups);
        $signups = $this->client->getSignups($league['type'], $league['id']);
        
        $this->assertCount(8, $signups);
    }
    
    /**
     * Test if we're able to start a league and retrieve matches
     */
    public function testLeagueMatchSetup()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        
        $this->assertCount(7, $rounds);
        
        foreach ($rounds as $round)
        {
            $this->assertCount(4, $round['stages'][0]['matches']);
            
            foreach ($round['stages'][0]['matches'] as $match)
            {
                $this->assertNotEmpty($match['signup'], 'signup missing in match!');
                $this->assertNotEmpty($match['signupOpponent'], 'signupOpponent missing in match!');
            }
        }
    }
    
    /**
     * Test that everyone has the same number of matches.
     */
    public function testLeagueMatchDistribution()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $matchCounts = array();
        
        foreach ($rounds as $round)
        {
            foreach ($round['stages'][0]['matches'] as $match)
            {
                if (!isset($matchCounts[$match['signup']['id']]))
                {
                    $matchCounts[$match['signup']['id']] = 0;
                }
                
                $matchCounts[$match['signup']['id']]++;
                
                if (!isset($matchCounts[$match['signupOpponent']['id']]))
                {
                    $matchCounts[$match['signupOpponent']['id']] = 0;
                }
                
                $matchCounts[$match['signupOpponent']['id']]++;
            }
        }
        
        $this->assertCount(8, $matchCounts);
        
        foreach ($matchCounts as $id => $count)
        {
            $this->assertEquals(7, $count, 'Signup ' . $id . ' has the wrong number of matches: ' . $count);
        }
    }
    
    public function testLeagueTable()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();
        
        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(8, $table);
    }
    
    public function testLeagueTableUpdates()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        
        $matches = $rounds[0]['stages'][0]['matches'];
        
        $this->client->updateMatch($league['type'], $league['id'], $matches[0]['id'], array(
            'score' => 4,
            'scoreOpponent' => 1,
        ));
        
        $this->client->updateMatch($league['type'], $league['id'], $matches[1]['id'], array(
            'score' => 2,
            'scoreOpponent' => 2,
        ));

        $this->client->updateMatch($league['type'], $league['id'], $matches[2]['id'], array(
            'score' => 3,
            'scoreOpponent' => 4,
        ));

        $this->client->updateMatch($league['type'], $league['id'], $matches[3]['id'], array(
            'score' => 4,
            'scoreOpponent' => 2,
        ));
        
        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(8, $table);
        
        // validate sorting
        $this->assertEquals($matches[0]['signup']['id'], $table[0]['signup']['id']);
        $this->assertEquals($matches[3]['signup']['id'], $table[1]['signup']['id']);
        $this->assertEquals($matches[2]['signupOpponent']['id'], $table[2]['signup']['id']);
        $this->assertEquals($matches[2]['signup']['id'], $table[5]['signup']['id']);
        $this->assertEquals($matches[3]['signupOpponent']['id'], $table[6]['signup']['id']);
        $this->assertEquals($matches[0]['signupOpponent']['id'], $table[7]['signup']['id']);
        
        $this->assertEquals(1, $table[0]['position']);
        $this->assertEquals(1, $table[0]['wins']);
        $this->assertEquals(0, $table[0]['draws']);
        $this->assertEquals(0, $table[0]['losses']);
        $this->assertEquals(3, $table[0]['points']);
        $this->assertEquals(3, $table[0]['score']);
        $this->assertEquals(4, $table[0]['scoreFor']);
        $this->assertEquals(1, $table[0]['scoreAgainst']);
        
        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals($league['roundCurrent'] + 1, $leagueFetched['roundCurrent']);
    }
    
    /**
     * Test if we're able to create championships
     */
    public function testCreateLeagueChampionship()
    {
        $name = uniqid('League ');
        
        $league = $this->createNewLeagueForTests(array(
            'name' => $name,
            'description' => 'Description here!',
            'leagueType' => 'championship',
            'roundCount' => 5,
        ));
        
        $this->assertEquals($name, $league['name']);
        $this->assertEquals('Description here!', $league['description']);
        $this->assertEquals('championship', $league['leagueType']);
        
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $this->assertCount(5, $rounds);
    }
    
    /**
     * Test if we're able to update round information for a championship
     */
    public function testUpdateLeagueRoundScoreChampionship()
    {
        $name = uniqid('League ');
        
        $league = $this->createLeagueWithSignupsAndStartIt(array(
            'name' => $name,
            'description' => 'Description here!',
            'leagueType' => 'championship',
            'roundCount' => 5,
            'scoringModelId' => 2,
        ));
        
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $this->assertCount(5, $rounds);
        
        $signups = $this->client->getSignups($league['type'], $league['id']);
        shuffle($signups);
        
        $results = array();
        
        for ($i = 0; $i < 8; $i++)
        {
            $results[] = array(
                'signupId' => $signups[$i]['id'],
                'position' => $i+1,
                'score' => 1000 - 50 * $i,
            );
        }
        
        $this->client->updateLeagueRoundResults($league['id'], 1, $results);
        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(8, $table);
        
        $model = $this->client->getLeagueScoringModel(2);
        $expectedPoints = array_values($model['positionPoints']);
        
        for ($i = 0; $i < 8; $i++)
        {
            $this->assertEquals($table[$i]['signup']['id'], $signups[$i]['id'], "Signup in position " . $i . " is not correct.");
            $this->assertEquals($table[$i]['points'], $expectedPoints[$i], "Wrong points in position " . $i);
        }
        
        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals($league['roundCurrent'] + 1, $leagueFetched['roundCurrent']);
    }
    
    /**
     * Test if we're able to update round information for a championship
     */
    public function testResetLeagueRoundScoreChampionship()
    {
        $name = uniqid('League ');
        
        $league = $this->createLeagueWithSignupsAndStartIt(array(
            'name' => $name,
            'description' => 'Description here!',
            'leagueType' => 'championship',
            'roundCount' => 5,
            'scoringModelId' => 2,
        ));
        
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $this->assertCount(5, $rounds);
        
        $signups = $this->client->getSignups($league['type'], $league['id']);
        shuffle($signups);
        
        $results = array();
        
        for ($i = 0; $i < 8; $i++)
        {
            $results[] = array(
                'signupId' => $signups[$i]['id'],
                'position' => $i+1,
                'score' => 1000 - 50 * $i,
            );
        }
        
        $this->client->updateLeagueRoundResults($league['id'], 1, $results);

        shuffle($signups);
        $results = array();
        
        for ($i = 0; $i < 8; $i++)
        {
            $results[] = array(
                'signupId' => $signups[$i]['id'],
                'position' => $i+1,
                'score' => 1000 - 50 * $i,
            );
        }
        
        $this->client->updateLeagueRoundResults($league['id'], 1, $results);
        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(8, $table);
        
        $model = $this->client->getLeagueScoringModel(2);
        $expectedPoints = array_values($model['positionPoints']);
        
        for ($i = 0; $i < 8; $i++)
        {
            $this->assertEquals($table[$i]['signup']['id'], $signups[$i]['id'], "Signup in position " . $i . " is not correct.");
            $this->assertEquals($table[$i]['points'], $expectedPoints[$i], "Wrong points in position " . $i);
        }
        
        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals($league['roundCurrent'] + 1, $leagueFetched['roundCurrent']);
    }
    
    /**
     * Run through a complete championship and ensure the championship is set as finished
     * and that the score is correct.
     */
    public function testCompleteChampionship()
    {
        $name = uniqid('League ');
        
        $league = $this->createLeagueWithSignupsAndStartIt(array(
            'name' => $name,
            'description' => 'Description here!',
            'leagueType' => 'championship',
            'roundCount' => 5,
            'scoringModelId' => 2,
            'teamCount' => 4,
        ));
        
        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $this->assertCount(5, $rounds);

        $signups = $this->client->getSignups($league['type'], $league['id']);
        shuffle($signups);
        
        $expectedResults = array(
            array(
                'signupId' => $signups[0]['id'],
                'points' => 18+25+25+25+25,
            ),
            array(
                'signupId' => $signups[1]['id'],
                'points' => 25+15+15+18+18,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'points' => 15+18+18+15+12,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'points' => 12+12+12+12+15,
            ),
        );
        
        $results = array();
        
        $this->client->updateLeagueRoundResults($league['id'], 1, array(
            array(
                'signupId' => $signups[1]['id'],
                'position' => 1,
                'score' => 114,
            ),
            array(
                'signupId' => $signups[0]['id'],
                'position' => 2,
                'score' => 118,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'position' => 3,
                'score' => 122,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'position' => 4,
                'score' => 128,
            ),
        ));
        
        $this->client->updateLeagueRoundResults($league['id'], 2, array(
            array(
                'signupId' => $signups[0]['id'],
                'position' => 1,
                'score' => 114,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'position' => 2,
                'score' => 118,
            ),
            array(
                'signupId' => $signups[1]['id'],
                'position' => 3,
                'score' => 122,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'position' => 4,
                'score' => 128,
            ),
        ));
        
        $this->client->updateLeagueRoundResults($league['id'], 3, array(
            array(
                'signupId' => $signups[0]['id'],
                'position' => 1,
                'score' => 114,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'position' => 2,
                'score' => 118,
            ),
            array(
                'signupId' => $signups[1]['id'],
                'position' => 3,
                'score' => 122,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'position' => 4,
                'score' => 128,
            ),
        ));
        
        $this->client->updateLeagueRoundResults($league['id'], 4, array(
            array(
                'signupId' => $signups[0]['id'],
                'position' => 1,
                'score' => 114,
            ),
            array(
                'signupId' => $signups[1]['id'],
                'position' => 2,
                'score' => 118,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'position' => 3,
                'score' => 122,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'position' => 4,
                'score' => 128,
            ),
        ));
        
        $this->client->updateLeagueRoundResults($league['id'], 5, array(
            array(
                'signupId' => $signups[0]['id'],
                'position' => 1,
                'score' => 114,
            ),
            array(
                'signupId' => $signups[1]['id'],
                'position' => 2,
                'score' => 118,
            ),
            array(
                'signupId' => $signups[3]['id'],
                'position' => 3,
                'score' => 122,
            ),
            array(
                'signupId' => $signups[2]['id'],
                'position' => 4,
                'score' => 128,
            ),
        ));        
        
        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        
        $this->assertEquals(6, $leagueFetched['roundCurrent']);
        $this->assertEquals('finished', $leagueFetched['status']);
        $this->assertNotEmpty($leagueFetched['finishedAt']);
        $table = $this->client->getLeagueTable($league['id']);
        
        $this->assertNotEmpty($table);
        
        foreach ($expectedResults as $idx => $entry)
        {
            $this->assertEquals($entry['signupId'], $table[$idx]['signup']['id']);
            $this->assertEquals($entry['points'], $table[$idx]['points']);
        }
    }
    
    /**
     * Test if the client is able to retrieve all available league scoring models
     */
    public function testRetrieveLeagueScoringModels()
    {
        $models = $this->client->getLeagueScoringModels();
        
        $this->assertNotEmpty($models);
    }
    
    /**
     * Test if the client is able to retrieve league scoring models for a specific type
     */
    public function testRetrieveLeagueScoringModelsOfType()
    {
        $types = array('league', 'championship');
        
        foreach ($types as $type)
        {
            $models = $this->client->getLeagueScoringModels($type);
        
            foreach ($models as $model)
            {
                $this->assertEquals($type, $model['type']);
            }
        }
    }    
    
    /**
     * Test if the client is able to retrieve a specific league scoring models
     */
    public function testRetrieveSpecificLeagueScoringModel()
    {
        $model = $this->client->getLeagueScoringModel(1);
        
        $this->assertNotEmpty($model);
        $this->assertEquals(1, $model['id']);
        $this->assertNotEmpty($model['name']);
        $this->assertEquals('league', $model['type']);
    }
    
    /**
     * Test if the client is able to retrieve a specific league scoring model with position points attached
     */
    public function testRetrieveLeagueScoringModelPositionPoints()
    {
        $model = $this->client->getLeagueScoringModel(2);
        
        $this->assertNotEmpty($model);
        $this->assertEquals(2, $model['id']);
        $this->assertNotEmpty($model['name']);
        $this->assertEquals('championship', $model['type']);
        
        $this->assertNotEmpty($model['positionPoints']);
        
        $expectedPositionPoints = array(
            1 => 25,
            2 => 18,
            3 => 15,
            4 => 12,
            5 => 10,
            6 => 8,
            7 => 6,
            8 => 4,
            9 => 2,
            10 => 1,
        );
        
        foreach ($model['positionPoints'] as $position => $points)
        {
            $this->assertEquals($expectedPositionPoints[$position], $points, "Wrong position point value for position: " . $position);
            unset($expectedPositionPoints[$position]);
        }
        
        $this->assertEmpty($expectedPositionPoints, "Some expected position point definitions where not returned as expected.");
    }
    
    /**
     * Internal method to create a league across test methods.
     * 
     * @param array $competitionInput To change any default values, supply better information here.
     * @return array A league created for further tests.
     */
    
    protected function createNewLeagueForTests($competitionInput = null)
    {
        $competitionInputValues = array(
            'name' => 'Test League ' . uniqid(),
            'gameId' => 3,
            'playersOnTeam' => 5,
            'countryId' => 1,
            'leagueType' => 'league',
            'teamCount' => 8,
            'scoringModelId' => 1,
        );
        
        if ($competitionInput)
        {
            $competitionInputValues = array_merge($competitionInputValues, $competitionInput);
        }
        
        return $this->client->createLeague($competitionInputValues);
    }
    
    /**
     * Internal method to set up, add teams and start a league
     * 
     * @return array
     */
    public function createLeagueWithSignupsAndStartIt($competitionInput = null)
    {
        $competitionInputValues = array(
            'name' => 'League ' . uniqid(),
            'teamCount' => 8,
        );
        
        if ($competitionInput)
        {
            $competitionInputValues = array_merge($competitionInputValues, $competitionInput);
        }        
        
        $league = $this->createNewLeagueForTests($competitionInputValues);
        
        $signups = $this->generateRandomSignups(8);
        $this->client->addSignups($league['type'], $league['id'], $signups);
        
        $this->client->startCompetition($league['type'], $league['id']);
        
        return $league;
    }    
}