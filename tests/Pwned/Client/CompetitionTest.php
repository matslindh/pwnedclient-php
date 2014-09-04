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
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );

        $competition = $this->createNewTournamentForTests($competitionInput);
        $rounds = $this->client->getRounds($competition['type'], $competition['id'], 'winner');
        $this->assertCount(4, $rounds);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertEquals($brackets[0]['roundCount'], count($rounds));

        // add check for actual content of rounds here..
        $this->assertCount(8, $rounds[0]['matches']);
        $this->assertCount(4, $rounds[1]['matches']);
        $this->assertCount(2, $rounds[2]['matches']);
        $this->assertCount(1, $rounds[3]['matches']);

        $this->assertEquals('Preliminary', $rounds[0]['identifier']);
        $this->assertEquals('Quarter-finals', $rounds[1]['identifier']);
        $this->assertEquals('Semi-finals', $rounds[2]['identifier']);
        $this->assertEquals('Final', $rounds[3]['identifier']);
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
            'countryId' => 1,
            'description' => 'Foobar Description #123',
        );

        $competition = $this->createNewTournamentForTests($competitionInput);
        $round = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner');

        $this->assertCount(8, $round['matches']);
        $this->assertEquals('Preliminary', $round['identifier']);
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
        // wether the correct match replacements happen is not a general test, so we'll just test that the method completes OK
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
            'teamCount' => 4,
        ));

        $this->client->addSignups($competition['type'], $competition['id'], $this->generateRandomSignups(4));

        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);
        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $called = false;
        $fetchedCount = 0;

        foreach ($brackets as $bracket)
        {
            foreach ($bracket['rounds'] as $round)
            {
                foreach ($round['matches'] as $match)
                {
                    $fetchedMatch = $this->client->getMatch($competition['type'], $competition['id'], $match['id']);
                    $fetchedCount++;

                    $this->assertNotEmpty($fetchedMatch);
                    $this->assertEquals($match['id'], $fetchedMatch['id']);
                }
            }
        }

        $this->assertEquals($competition['teamCount'] - 1, $fetchedCount);
    }

    /**
     * Test storage of match results.
     */
    public function testUpdateMatch()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'teamCount' => 4,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);

        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);

        $rounds = $this->client->getRounds($competition['type'], $competition['id'], 'winner');

        $this->assertCount(2, $rounds);
        $scores = array();
        $matchesUpdated = 0;

        foreach ($rounds as $round)
        {
            foreach ($round['matches'] as $match)
            {
                $scores[$match['id']] = array(
                    'score' => rand(1, 50),
                    'scoreOpponent' => rand(1, 50),
                );

                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], $scores[$match['id']]);
                $matchesUpdated++;
            }
        }

        $this->assertEquals($competition['teamCount'] - 1, $matchesUpdated);

        $rounds = $this->client->getRounds($competition['type'], $competition['id'], 'winner');

        foreach ($rounds as $round)
        {
            foreach ($round['matches'] as $match)
            {
                $this->assertEquals($scores[$match['id']]['score'], $match['score']);
                $this->assertEquals($scores[$match['id']]['scoreOpponent'], $match['scoreOpponent']);
            }
        }
    }

    /**
     * Test storage of a walkover result
     */
    public function testUpdateMatchWalkoverUndo()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'teamCount' => 4,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);

        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);
        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => 'signupOpponent',
        ));

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertTrue($match['isWalkover']);

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => false,
        ));

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertNull($match['score']);
        $this->assertNull($match['scoreOpponent']);
        $this->assertFalse($match['isWalkover']);
    }

    /**
     * Test storage of a walkover result
     */
    public function testUpdateMatchWalkover()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'teamCount' => 4,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);

        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => 'signupOpponent',
        ));

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertEquals(0, $match['score']);
        $this->assertEquals(1, $match['scoreOpponent']);
        $this->assertTrue($match['isWalkover']);
    }

    /**
     * Test that we can store an optional score together with the walkover state.
     */
    public function testUpdateMatchWalkoverWithScore()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'MatchTest',
            'teamCount' => 4,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);

        // start tournament
        $this->client->startCompetition($competition['type'], $competition['id']);

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => 'signupOpponent',
            'score' => 0,
            'scoreOpponent' => 15,
        ));

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertEquals(0, $match['score']);
        $this->assertEquals(15, $match['scoreOpponent']);
        $this->assertTrue($match['isWalkover']);

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => false,
        ));

        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertNull($match['score']);
        $this->assertNull($match['scoreOpponent']);
        $this->assertFalse($match['isWalkover']);

        // this will trigger an error, so we unbind the error handler..
        $handler = $this->client->getErrorCallback();
        $this->client->setErrorCallback(null);

        // try to set the score to an invalid score for the walkover value..
        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'walkover' => 'signupOpponent',
            'score' => 15,
            'scoreOpponent' => 0,
        ));

        // reset the error callback
        $this->client->setErrorCallback($handler);

        // make sure the match wasn't updated.
        $match = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner')['matches'][0];
        $this->assertNull($match['score']);
        $this->assertNull($match['scoreOpponent']);
        $this->assertFalse($match['isWalkover']);
    }

    /**
     * Test that we get an error if we try to retrieve a competition with the wrong type (from the wrong endpoint).
     */
    public function testRetrieveWrongCompetitionEndPoint()
    {
        $competition = $this->createNewTournamentForTests();

        $this->client->disableDebugging();
        $fetched = $this->client->getCompetition('league', $competition['id']);
        $this->client->enableDebugging();
        $this->assertEmpty($fetched);
    }
}