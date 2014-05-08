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

        $this->assertEquals('league', $league['type']);
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
            $this->assertCount(4, $round['matches']);

            foreach ($round['matches'] as $match)
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
            foreach ($round['matches'] as $match)
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

    /**
     * Are we able to retrieve the table for the league?
     */
    public function testLeagueTable()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();

        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(8, $table);
    }

    /**
     * Did the table for the league get updated after submitting match results?
     */
    public function testLeagueTableUpdates()
    {
        $league = $this->createLeagueWithSignupsAndStartIt();
        $rounds = $this->client->getRounds($league['type'], $league['id']);

        $matches = $rounds[0]['matches'];

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
     * Test a complete run-through of a regular league
     */
    public function testCompleteLeague()
    {
        $name = uniqid('League ');

        $league = $league = $this->createLeagueWithSignupsAndStartIt(array(
            'teamCount' => 4,
        ));

        $rounds = $this->client->getRounds($league['type'], $league['id']);
        $this->assertCount(3, $rounds);

        $matches = array(
            array(
                array(3, 1),
                array(2, 0),
            ),
            array(
                array(3, 4),
                array(8, 3),
            ),
            array(
                array(3, 2),
                array(2, 2),
            ),
        );

        $expectedStates = array();

        foreach ($matches as $roundIdx => $matchArray)
        {
            $round = $rounds[$roundIdx];

            foreach ($matchArray as $matchIdx => $results)
            {
                $match = $round['matches'][$matchIdx];

                $this->client->updateMatch($league['type'], $league['id'], $match['id'], array(
                    'score' => $results[0],
                    'scoreOpponent' => $results[1],
                ));

                if (!isset($expectedStates[$match['signup']['id']]))
                {
                    $expectedStates[$match['signup']['id']] = array(
                        'points' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'score' => 0,
                    );
                }

                if (!isset($expectedStates[$match['signupOpponent']['id']]))
                {
                    $expectedStates[$match['signupOpponent']['id']] = array(
                        'points' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'score' => 0,
                    );
                }

                if ($results[0] > $results[1])
                {
                    $expectedStates[$match['signup']['id']]['wins']++;
                    $expectedStates[$match['signup']['id']]['score'] += $results[0] - $results[1];
                    $expectedStates[$match['signup']['id']]['points'] += 3;

                    $expectedStates[$match['signupOpponent']['id']]['losses']++;
                    $expectedStates[$match['signupOpponent']['id']]['score'] += $results[1] - $results[0];
                }
                else if ($results[0] < $results[1])
                {
                    $expectedStates[$match['signup']['id']]['losses']++;
                    $expectedStates[$match['signup']['id']]['score'] += $results[0] - $results[1];

                    $expectedStates[$match['signupOpponent']['id']]['wins']++;
                    $expectedStates[$match['signupOpponent']['id']]['score'] += $results[1] - $results[0];
                    $expectedStates[$match['signupOpponent']['id']]['points'] += 3;
                }
                else
                {
                    $expectedStates[$match['signup']['id']]['draws']++;
                    $expectedStates[$match['signup']['id']]['points'] += 1;

                    $expectedStates[$match['signupOpponent']['id']]['draws']++;
                    $expectedStates[$match['signupOpponent']['id']]['points'] += 1;
                }
            }
        }

        $fetchedLeague = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals('finished', $fetchedLeague['status']);
        $this->assertNotEmpty($fetchedLeague['finishedAt']);

        $table = $this->client->getLeagueTable($league['id']);

        foreach ($table as $entry)
        {
            $expEntry = $expectedStates[$entry['signup']['id']];

            $this->assertEquals($expEntry['wins'], $entry['wins'], 'The number of wins were not as expected.');
            $this->assertEquals($expEntry['draws'], $entry['draws'], 'The number of draws were not as expected.');
            $this->assertEquals($expEntry['losses'], $entry['losses'], 'The number of losses were not as expected.');
            $this->assertEquals($expEntry['score'], $entry['score'], 'The score was not as expected.');
            $this->assertEquals($expEntry['points'], $entry['points'], 'The number of points were not expected number of points.');
        }
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
     * Test if we can create a scoring model.
     */
    public function testLeagueCreateScoringModel()
    {
        $name = 'ScoringModelTest ' . uniqid();

        $scoringModel = $this->client->createLeagueScoringModel(array(
            'name' => $name,
            'type' => 'league',
            'description' => 'This is a description of this scoring model.',
            'winPoints' => 3,
            'drawPoints' => 1,
            'lossPoints' => 0,
        ));

        $this->assertNotEmpty($scoringModel);
        $this->assertEquals($name, $scoringModel['name']);
        $this->assertEquals('This is a description of this scoring model.', $scoringModel['description']);
    }

    /**
     * Test if we can create a scoring model with a position / points list.
     */
    public function testLeagueCreateScoringModelWithPositionPointsList()
    {
        $name = 'ScoringModelTest ' . uniqid();
        $positionPointsList = array(
            1 => 30,
            2 => 15,
            3 => 5,
        );

        $scoringModel = $this->client->createLeagueScoringModel(array(
            'name' => $name,
            'type' => 'championship',
            'description' => 'This is a description of this scoring model.',
            'positionPoints' => $positionPointsList,
        ));

        $this->assertNotEmpty($scoringModel);
        $this->assertEquals($name, $scoringModel['name']);
        $this->assertEquals('This is a description of this scoring model.', $scoringModel['description']);
        $this->assertEquals($positionPointsList, $scoringModel['positionPoints']);
    }

    /**
     * Test if we can update a scoring model.
     */
    public function testLeagueUpdateScoringModel()
    {
        $name = 'ScoringModelTest ' . uniqid();

        $scoringModelOld = $this->client->createLeagueScoringModel(array(
            'name' => $name,
            'type' => 'league',
            'description' => 'This is a description of this scoring model.',
            'winPoints' => 3,
            'drawPoints' => 1,
            'lossPoints' => 0,
        ));

        $this->assertNotEmpty($scoringModelOld);

        $name = 'ScoringModelTest ' . uniqid();

        $scoringModelUpdated = $this->client->updateLeagueScoringModel($scoringModelOld['id'], array(
            'name' => $name,
            'type' => 'league',
            'description' => 'This is the new description of the scoring model.',
            'winPoints' => 1,
            'drawPoints' => 2,
            'lossPoints' => 3,
        ));

        $this->assertNotEmpty($scoringModelUpdated);
        $this->assertEquals($name, $scoringModelUpdated['name']);
        $this->assertEquals('This is the new description of the scoring model.', $scoringModelUpdated['description']);
        $this->assertEquals($scoringModelOld['id'], $scoringModelUpdated['id']);
        $this->assertEquals(1, $scoringModelUpdated['winPoints']);
        $this->assertEquals(2, $scoringModelUpdated['drawPoints']);
        $this->assertEquals(3, $scoringModelUpdated['lossPoints']);
    }

    /**
     * Test if we're able to delete a scoring model.
     */
    public function testLeagueDeleteScoringModel()
    {
        $name = 'ScoringModelTest ' . uniqid();

        $scoringModel = $this->client->createLeagueScoringModel(array(
            'name' => $name,
            'type' => 'league',
            'description' => 'This is a description of this scoring model.',
            'winPoints' => 3,
            'drawPoints' => 1,
            'lossPoints' => 0,
        ));

        $scoringModelCount = count($this->client->getLeagueScoringModels());
        $this->client->deleteLeagueScoringModel($scoringModel['id']);
        $newScoringModelCount = count($this->client->getLeagueScoringModels());
        $this->assertLessThan($scoringModelCount, $newScoringModelCount);

        $scoringModel = $this->client->getLeagueScoringModel($scoringModel['id']);
        $this->assertFalse($scoringModel['active']);
    }

    /**
     * Test creating multiple leagues in the same statement.
     */
    public function testCreateMultipleLeagues()
    {
        $bundle = $this->client->createBundle(array(
            'name' => 'Foo',
        ));

        $this->assertEquals('Foo', $bundle['name']);
        $this->assertNotEmpty($bundle['id']);

        $leagues = $this->client->createLeague(array(
            'leagueType' => 'league',
            'teamCount' => 8,
            'scoringModelId' => 1,
            'leagueCount' => 4,
            'bundleId' => $bundle['id'],
        ));

        $this->assertCount(4, $leagues);

        foreach ($leagues as $league)
        {
            $this->assertNotEmpty($league['id']);
            $this->assertEquals(8, $league['teamCount']);
            $this->assertEquals($bundle['id'], $league['bundleId']);
        }
    }

    /**
     * Test that we can update the teamCount for a League
     */
    public function testUpdatedTeamCount()
    {
        $league = $this->createNewLeagueForTests(array(
            'teamCount' => 8,
        ));

        $this->assertEquals(8, $league['teamCount']);

        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals(8, $leagueFetched['teamCount']);

        $this->client->updateCompetition($league['type'], $league['id'], array(
            'teamCount' => 5,
        ));

        $leagueFetched = $this->client->getCompetition($league['type'], $league['id']);
        $this->assertEquals(5, $leagueFetched['teamCount']);
    }


    /**
     * Test that unfinishedMatches are populated correctly for each round.
     */
    public function testPopulatedUnfinishedMatches()
    {
        $competition = $this->createLeagueWithSignupsAndStartIt(array(
        ));

        $rounds = $this->client->getRounds('league', $competition['id']);
        $this->assertNotEmpty($rounds);

        foreach ($rounds as $round)
        {
            $this->assertEquals(count($round['matches']), $round['unfinishedMatches']);
        }
    }

    /**
     * Confirm that each team only plays each other once
     */
    public function testValidLeagueSetup()
    {
        $competition = $this->createLeagueWithSignupsAndStartIt(array(
            'teamCount' => 8,
        ));

        $playsEachOther = array();
        $rounds = $this->client->getRounds('league', $competition['id']);
        $this->assertCount(7, $rounds);

       foreach ($rounds as $round)
       {
           foreach ($round['matches'] as $match)
           {
               $lesser = min($match['signup']['id'], $match['signupOpponent']['id']);
               $larger = max($match['signup']['id'], $match['signupOpponent']['id']);

               $this->assertTrue(empty($playsEachOther[$lesser][$larger]), "Two teams have played each other before: " . $match['signup']['name'] . ' vs ' . $match['signupOpponent']['name']);
               $playsEachOther[$lesser][$larger] = true;
           }
       }
    }

    /**
     * Test that we get the correct number of matches for a league with an un-even number of teams.
     */
    public function testUnevenSignupCountForLeague()
    {
        $competition = $this->createLeagueWithSignupsAndStartIt(array(
            'teamCount' => 7,
        ));

        $signups = $this->client->getSignups('league', $competition['id']);
        $rounds = $this->client->getRounds('league', $competition['id']);

        $walkoverSignupIds = array();

        foreach ($signups as $signup)
        {
            $walkoverSignupIds[$signup['id']] = true;
        }

        $this->assertCount(7, $rounds);

        foreach ($rounds as $round)
        {
            $this->assertCount(4, $round['matches']);
            $this->assertEquals(3, $round['unfinishedMatches']);
            $this->assertEquals(1, $round['finishedMatches']);

            foreach ($round['matches'] as $match)
            {
                if (!$match['signup'])
                {
                    $this->assertNotEmpty($walkoverSignupIds[$match['signupOpponent']['id']]);
                    unset($walkoverSignupIds[$match['signupOpponent']['id']]);
                }

                if (!$match['signupOpponent'])
                {
                    $this->assertNotEmpty($walkoverSignupIds[$match['signup']['id']]);
                    unset($walkoverSignupIds[$match['signup']['id']]);
                }
            }
        }

        $this->assertEmpty($walkoverSignupIds);
    }

    /**
     * Test that we can retire an existing player / team from a league and that the stats gets updated.
     */
    public function testRetirePlayerAndMatches()
    {
        $league = $this->createLeagueWithSignupsAndStartIt(array(
            'teamCount' => 6,
        ));

        $signups = $this->client->getSignups('league', $league['id']);
        $rounds = $this->client->getRounds('league', $league['id']);

        $this->assertCount(5, $rounds);

        foreach (array_slice($rounds, 0, 3) as $round)
        {
            foreach ($round['matches'] as $match)
            {
                $score = rand(1,1000);
                $scoreOpponent = rand(1,1000);

                $this->client->updateMatch($league['type'], $league['id'], $match['id'], array(
                    'score' => $score,
                    'scoreOpponent' => $scoreOpponent,
                ));
            }
        }

        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(6, $table);

        // retire number three in ranking
        $this->client->retireLeagueSignup($league['id'], $table[2]['signup']['id']);

        $newTable = $this->client->getLeagueTable($league['id']);
        $this->assertCount(6, $newTable);

        // assert the previous player is dead last (other players may have moved around "randomly", so we just test this..
        $this->assertEquals($table[2]['signup']['id'], $newTable[5]['signup']['id']);

        // assert the player was retired
        $this->assertTrue($newTable[5]['signup']['retired']);
        $this->assertEquals(0, $newTable[5]['wins']);
        $this->assertEquals(0, $newTable[5]['draws']);
        $this->assertEquals(0, $newTable[5]['losses']);
        $this->assertEquals(0, $newTable[5]['played']);
        $this->assertEquals(0, $newTable[5]['scoreFor']);
        $this->assertEquals(0, $newTable[5]['scoreAgainst']);
        $this->assertEquals(0, $newTable[5]['score']);
        $this->assertEquals(0, $newTable[5]['points']);

        // check that we have corrected the scores for the other teams / players as well
        foreach ($newTable as $entry)
        {
            $this->assertEquals($entry['wins'] * 3 + $entry['draws'], $entry['points']);
        }
    }

    /**
     * Test that we can retire an existing player / team from a league and that the stats gets updated.
     */
    public function testRetirePlayerAndMatchesInUnevenSetup()
    {
        $league = $this->createLeagueWithSignupsAndStartIt(array(
            'teamCount' => 7,
        ));

        $signups = $this->client->getSignups('league', $league['id']);
        $rounds = $this->client->getRounds('league', $league['id']);

        $this->assertCount(7, $rounds);

        foreach (array_slice($rounds, 0, 3) as $round)
        {
            foreach ($round['matches'] as $match)
            {
                $score = rand(1,1000);
                $scoreOpponent = rand(1,1000);

                $this->client->updateMatch($league['type'], $league['id'], $match['id'], array(
                    'score' => $score,
                    'scoreOpponent' => $scoreOpponent,
                ));
            }
        }

        $table = $this->client->getLeagueTable($league['id']);
        $this->assertCount(7, $table);

        // retire number three in ranking
        $this->client->retireLeagueSignup($league['id'], $table[2]['signup']['id']);

        $newTable = $this->client->getLeagueTable($league['id']);
        $this->assertCount(7, $newTable);

        // assert the previous player is dead last (other players may have moved around "randomly", so we just test this..
        $this->assertEquals($table[2]['signup']['id'], $newTable[6]['signup']['id']);

        // assert the player was retired
        $this->assertTrue($newTable[6]['signup']['retired']);
        $this->assertEquals(0, $newTable[6]['wins']);
        $this->assertEquals(0, $newTable[6]['draws']);
        $this->assertEquals(0, $newTable[6]['losses']);
        $this->assertEquals(0, $newTable[6]['played']);
        $this->assertEquals(0, $newTable[6]['scoreFor']);
        $this->assertEquals(0, $newTable[6]['scoreAgainst']);
        $this->assertEquals(0, $newTable[6]['score']);
        $this->assertEquals(0, $newTable[6]['points']);

        // check that we have corrected the scores for the other teams / players as well
        foreach ($newTable as $entry)
        {
            $this->assertEquals($entry['wins'] * 3 + $entry['draws'], $entry['points']);
        }
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

        $signups = $this->generateRandomSignups($competitionInputValues['teamCount']);
        $this->client->addSignups($league['type'], $league['id'], $signups);

        $this->client->startCompetition($league['type'], $league['id']);

        return $league;
    }
}