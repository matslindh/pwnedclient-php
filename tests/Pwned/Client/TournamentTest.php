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
            'tournamentType' => 'singleelim',
            'teamCount' => 16,
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
        $this->assertEquals('ready', $competition['status']);
        $this->assertEquals(16, $competition['teamCount']);
        $this->assertEquals('singleelim', $competition['tournamentType']);
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
            'tournamentType' => 'doubleelim',
            'teamCount' => 4,
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
        $this->assertEquals('ready', $competition['status']);
        $this->assertEquals(8, $competition['teamCount']);
        $this->assertEquals(4, $competition['groupSize']);
        $this->assertEquals(2, $competition['groupCount']);

        return $competition;
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
            'tournamentType' => 'singleelim',
            'teamCount' => 16,
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
        $this->assertEquals($competitionFetched['tournamentType'], $competition['tournamentType']);
        $this->assertEquals($competitionFetched['teamCount'], $competition['teamCount']);
        $this->assertEquals('ready', $competitionFetched['status']);
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
            'tournamentType' => 'doubleelim',
            'teamCount' => 8,
        ));

        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals('doubleelim', $updatedCompetition['tournamentType']);
        $this->assertEquals('8', $updatedCompetition['teamCount']);
        $this->assertCount(3, $this->client->getTournamentBrackets($competition['id']));
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
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
            'groupSize' => 8,
            'groupCount' => 4,
        ));

        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals(8, $updatedCompetition['groupSize']);
        $this->assertEquals(4, $updatedCompetition['groupCount']);
        $this->assertEquals('ready', $updatedCompetition['status']);
        $this->assertEquals(32, $updatedCompetition['teamCount']);
        $this->assertEquals(8, $updatedCompetition['eliminationTeamCount']);
    }

    /**
     * Test if we remove a group size setting after setting it
     *
     * @depends testCreateTournament
     */
    public function testUpdateTournamentRemoveGroupInformation($competition)
    {
        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
            'groupSize' => 8,
            'groupCount' => 4,
        ));

        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals(8, $updatedCompetition['groupSize']);
        $this->assertEquals(4, $updatedCompetition['groupCount']);
        $this->assertEquals(32, $updatedCompetition['teamCount']);
        $this->assertEquals(8, $updatedCompetition['eliminationTeamCount']);

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
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
            'groupSize' => 8,
            'groupCount' => 4,
        ));

        $updatedCompetition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEquals(8, $updatedCompetition['groupSize']);
        $this->assertEquals(4, $updatedCompetition['groupCount']);
        $this->assertEquals(32, $updatedCompetition['teamCount']);
        $this->assertEquals(8, $updatedCompetition['eliminationTeamCount']);

        $this->client->updateCompetition($competition['type'], $competition['id'], array(
            'groupSize' => null,
            'groupCount' => null,
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
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
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
            'playersOnTeam' => 1,
            'gameId' => 1,
            'countryId' => 1,
            'quickProgress' => false,
        ));

        $signups = $this->generateRandomSignups(6);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $round = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner');

        $matches = $round['matches'];

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

        $response = $this->client->moveTournamentWalkoversToNextRound($competition['type'], $competition['id'], $round['id']);
        $round = $this->client->getRound($competition['type'], $competition['id'], 2, 'winner');

        foreach ($round['matches'] as $match)
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

    /**
     * Test a complete, single elimination tournament from start to end.
     */
    public function testCompleteSingleEliminationTournament()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Complete Single Elimination Tournament Test',
            'tournamentType' => 'singleelim',
            'teamCount' => 8,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups(8);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $signups = $this->client->getSignups($competition['type'], $competition['id']);
        $expectedSignupIds = array();
        $expectedResults = array();

        foreach ($signups as $signup)
        {
            $expectedSignupIds[$signup['id']] = true;
            $expectedResults[$signup['id']] = array(
                'round' => 1,
                'score' => 0,
            );
        }

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertCount(1, $brackets);

        $bracket = $brackets[0];
        $expectedRoundCount = log($competition['teamCount'], 2);
        $this->assertEquals('winner', $bracket['name']);
        $this->assertEquals($expectedRoundCount, $bracket['roundCount']);
        $this->assertEquals(1, $bracket['roundCurrent']);

        for ($i = 0; $i < $expectedRoundCount; $i++)
        {
            $round = $this->client->getRound($competition['type'], $competition['id'], $i+1, $bracket['name']);
            $matches = $round['matches'];
            $expectedMatchCount = (int) pow(2, $expectedRoundCount - $i - 1);
            $this->assertCount($expectedMatchCount, $matches);
            $this->assertEquals(0, $round['finishedMatches']);
            $this->assertEquals($expectedMatchCount, $round['unfinishedMatches']);

            $nextRoundExpectedSignupIds = array();

            $matchIdx = 1;

            foreach ($matches as $match)
            {
                $this->assertNotEmpty($match['signup']['id']);
                $this->assertNotEmpty($match['signupOpponent']['id']);
                $this->assertArrayHasKey($match['signup']['id'], $expectedSignupIds);
                $this->assertArrayHasKey($match['signupOpponent']['id'], $expectedSignupIds);

                unset($expectedSignupIds[$match['signupOpponent']['id']]);
                unset($expectedSignupIds[$match['signup']['id']]);

                $score = rand(1,300);
                $scoreOpponent = rand(1, 300);

                while ($scoreOpponent == $score)
                {
                    $scoreOpponent = rand(1, 300);
                }

                if ($score > $scoreOpponent)
                {
                    $nextRoundExpectedSignupIds[$match['signup']['id']] = true;
                }
                else
                {
                    $nextRoundExpectedSignupIds[$match['signupOpponent']['id']] = true;
                }

                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => $score,
                    'scoreOpponent' => $scoreOpponent,
                ));

                $roundFetched = $this->client->getRound($competition['type'], $competition['id'], $i+1, $bracket['name']);
                $this->assertEquals($expectedMatchCount - $matchIdx, $roundFetched['unfinishedMatches']);
                $this->assertEquals($matchIdx, $roundFetched['finishedMatches']);

                $matchIdx++;
            }

            $bracketsFetched = $this->client->getTournamentBrackets($competition['id']);
            $this->assertEquals($i+2, $bracketsFetched[0]['roundCurrent']);
            $this->assertEmpty($expectedSignupIds);
            $expectedSignupIds = $nextRoundExpectedSignupIds;
        }

        $competition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertNotEmpty($competition['finishedAt']);
        $this->assertEquals($competition['status'], 'finished');

        foreach ($this->client->getTournamentBrackets($competition['id']) as $bracket)
        {
            $this->assertNotEmpty($bracket['finishedAt']);
        }
    }

    /**
     * Test a complete, double elimination tournament from start to end.
     */
    public function testCompleteDoubleEliminationTournament()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Complete Double Elimination Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 8,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups($competition['teamCount']);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $signups = $this->client->getSignups($competition['type'], $competition['id']);
        $expectedSignupIds = array();
        $expectedResults = array();
        $expectedLoserSignupIds = array();

        foreach ($signups as $signup)
        {
            $expectedSignupIds[$signup['id']] = true;
            $expectedResults[$signup['id']] = array(
                'round' => 1,
                'score' => 0,
            );
        }

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertCount(3, $brackets);

        $expectedRoundCount = log($competition['teamCount'], 2);
        $this->assertEquals('winner', $brackets[0]['name']);
        $this->assertEquals($expectedRoundCount, $brackets[0]['roundCount']);
        $this->assertEquals(1, $brackets[0]['roundCurrent']);

        $this->assertEquals('loser', $brackets[1]['name']);
        $this->assertEquals(4, $brackets[1]['roundCount']);
        $this->assertEquals(1, $brackets[1]['roundCurrent']);

        $this->assertEquals('final', $brackets[2]['name']);
        $this->assertEquals(1, $brackets[2]['roundCount']);
        $this->assertEquals(1, $brackets[2]['roundCurrent']);

        $loserBracketMapping = array(0, 1);
        $loserBracketIndex = 3;

        for ($i = 2; $i < $brackets[0]['roundCount']; $i++)
        {
            $loserBracketMapping[] = $loserBracketIndex;
            $loserBracketIndex += 2;
        }

        for ($i = 0; $i < $brackets[1]['roundCount']; $i++)
        {
            $expectedLoserSignupIds[$i] = array();
        }

        // test winner bracket
        for ($i = 0; $i < $expectedRoundCount; $i++)
        {
            $round = $this->client->getRound($competition['type'], $competition['id'], $i+1, $brackets[0]['name']);
            $matches = $round['matches'];
            $this->assertCount((int) pow(2, $expectedRoundCount - $i - 1), $matches);

            $nextRoundExpectedSignupIds = array();
            $expectedLoserSignupIds[$loserBracketMapping[$i]] = array();

            foreach ($matches as $match)
            {
                $this->assertNotEmpty($match['signup']['id']);
                $this->assertNotEmpty($match['signupOpponent']['id']);
                $this->assertArrayHasKey($match['signup']['id'], $expectedSignupIds);
                $this->assertArrayHasKey($match['signupOpponent']['id'], $expectedSignupIds);

                unset($expectedSignupIds[$match['signupOpponent']['id']]);
                unset($expectedSignupIds[$match['signup']['id']]);

                $score = rand(1,300);
                $scoreOpponent = rand(1, 300);

                while ($scoreOpponent == $score)
                {
                    $scoreOpponent = rand(1, 300);
                }

                if ($score > $scoreOpponent)
                {
                    $nextRoundExpectedSignupIds[$match['signup']['id']] = true;
                    $expectedLoserSignupIds[$loserBracketMapping[$i]][$match['signupOpponent']['id']] = true;
                }
                else
                {
                    $nextRoundExpectedSignupIds[$match['signupOpponent']['id']] = true;
                    $expectedLoserSignupIds[$loserBracketMapping[$i]][$match['signup']['id']] = true;
                }

                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => $score,
                    'scoreOpponent' => $scoreOpponent,
                ));
            }

            $bracketsFetched = $this->client->getTournamentBrackets($competition['id']);
            $this->assertEquals($i+2, $bracketsFetched[0]['roundCurrent']);
            $this->assertEmpty($expectedSignupIds);
            $expectedSignupIds = $nextRoundExpectedSignupIds;
        }

        $competition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertEmpty($competition['finishedAt']);

        // the last winning entry
        $expectedFinal = $nextRoundExpectedSignupIds;
        $expectedRoundCounts = array(2, 2, 1, 1);

        // test loser bracket
        for ($i = 0; $i < count($expectedRoundCounts); $i++)
        {
            $round = $this->client->getRound($competition['type'], $competition['id'], $i+1, $brackets[1]['name']);
            $matches = $round['matches'];
            $this->assertCount($expectedRoundCounts[$i], $matches);

            $nextRoundExpectedSignupIds = array();

            foreach ($matches as $match)
            {
                $this->assertNotEmpty($match['signup']['id']);
                $this->assertNotEmpty($match['signupOpponent']['id']);
                $this->assertArrayHasKey($match['signup']['id'], $expectedLoserSignupIds[$i]);
                $this->assertArrayHasKey($match['signupOpponent']['id'], $expectedLoserSignupIds[$i]);

                unset($expectedLoserSignupIds[$i][$match['signupOpponent']['id']]);
                unset($expectedLoserSignupIds[$i][$match['signup']['id']]);

                $score = rand(1,300);
                $scoreOpponent = rand(1, 300);

                while ($scoreOpponent == $score)
                {
                    $scoreOpponent = rand(1, 300);
                }

                if ($score > $scoreOpponent)
                {
                    $expectedLoserSignupIds[$i+1][$match['signup']['id']] = true;
                }
                else
                {
                    $expectedLoserSignupIds[$i+1][$match['signupOpponent']['id']] = true;
                }

                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => $score,
                    'scoreOpponent' => $scoreOpponent,
                ));
            }

            $bracketsFetched = $this->client->getTournamentBrackets($competition['id']);
            $this->assertEquals($i+2, $bracketsFetched[1]['roundCurrent']);

            $this->assertEmpty($expectedLoserSignupIds[$i]);
            $expectedSignupIds = $nextRoundExpectedSignupIds;
        }

        $expectedFinal += $expectedLoserSignupIds[$i];

        // test final bracket
        $round = $this->client->getRound($competition['type'], $competition['id'], 1, $brackets[2]['name']);
        $this->assertCount(1, $round['matches']);

        $match = $round['matches'][0];

        $this->assertNotEmpty($match['signup']['id']);
        $this->assertNotEmpty($match['signupOpponent']['id']);
        $this->assertArrayHasKey($match['signup']['id'], $expectedFinal);
        $this->assertArrayHasKey($match['signupOpponent']['id'], $expectedFinal);

        $score = rand(1,300);
        $scoreOpponent = rand(1, 300);

        while ($scoreOpponent == $score)
        {
            $scoreOpponent = rand(1, 300);
        }

        $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
            'score' => $score,
            'scoreOpponent' => $scoreOpponent,
        ));

        $competition = $this->client->getCompetition($competition['type'], $competition['id']);
        $this->assertNotEmpty($competition['finishedAt']);

        foreach ($this->client->getTournamentBrackets($competition['id']) as $bracket)
        {
            $this->assertNotEmpty($bracket['finishedAt']);
        }
    }

    /**
     * Test a complete, group stage and single elimination tournament from start to end.
     */
    public function testCompleteGroupEliminationTournament()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Complete Double Elimination Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 4,
            'playersOnTeam' => 1,
            'gameId' => 1,
            'groupCount' => 2,
            'groupSize' => 4,
        ));

        $signups = $this->generateRandomSignups($competition['teamCount']);
        $signupIds = $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertCount(4, $brackets);

        $signupIdsTest = array_flip($signupIds);

        $names = array ('groups', 'winner', 'loser', 'final');
        $i = 0;

        foreach ($brackets as $bracket)
        {
            $this->assertEquals($bracket['name'], $names[$i++]);
        }

        $bracket = $brackets[0];
        $round = $bracket['rounds'][0];

        $this->assertCount(2, $round['groups']);
        $alreadyRising = true;

        foreach ($round['groups'] as $group)
        {
            $this->assertCount(4, $group['standing']);
            $previousSignupId = false;

            foreach ($group['standing'] as $standing)
            {
                $this->assertNotEmpty($signupIdsTest[$standing['signup']['id']]);
                unset($signupIdsTest[$standing['signup']['id']]);

                if ($previousSignupId && ($standing['signup']['id'] < $previousSignupId))
                {
                    $alreadyRising = false;
                }

                $previousSignupId = $standing['signup']['id'];
            }
        }

        $this->assertEmpty($signupIdsTest);

        foreach ($round['groups'] as $group)
        {
            foreach ($group['stages'] as $stage)
            {
                foreach ($stage['matches'] as $match)
                {
                    $score = (int) !$alreadyRising;
                    $scoreOpponent = (int) $alreadyRising;

                    if ($match['signup']['id'] < $match['signupOpponent']['id'])
                    {
                        $score = (int) $alreadyRising;
                        $scoreOpponent = (int) !$alreadyRising;
                    }

                    $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                        'score' => $score,
                        'scoreOpponent' => $scoreOpponent,
                    ));
                }
            }
        }

        $roundUpdated = $this->client->getRound($competition['type'], $competition['id'], 1, $brackets[0]['name']);

        $sortedSignups = array_merge(array_slice($roundUpdated['groups'][0]['standing'], 0, 2), array_slice($roundUpdated['groups'][1]['standing'], 0, 2));
        $sortedSignupIds = array();

        foreach ($sortedSignups as $groupSignup)
        {
            $sortedSignupIds[$groupSignup['signup']['id']] = true;
        }

        $this->assertCount(4, $sortedSignupIds);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertNotEmpty($brackets[0]['finishedAt']);
        $this->assertCount(2, $brackets[1]['rounds'][0]['matches']);

        foreach ($brackets[1]['rounds'][0]['matches'] as $match)
        {
            $this->assertNotEmpty($match['signup']);
            $this->assertNotEmpty($match['signupOpponent']);

            $this->assertArrayHasKey($match['signup']['id'], $sortedSignupIds);
            $this->assertArrayHasKey($match['signupOpponent']['id'], $sortedSignupIds);

            unset($sortedSignupIds[$match['signup']['id']]);
            unset($sortedSignupIds[$match['signupOpponent']['id']]);
        }

        $this->assertEmpty($sortedSignupIds);

        // the rest of the tournament should be identical to already completed tests.
        // add more testing if necessary later.
    }

    /**
     * Tests group stages and progressing into tournament with uneven amount of groups / rank spots
     */
    public function testUnevenGroupStageAndProgression()
    {
        $tournament = $this->createNewTournamentForTests(array(
            'name' => 'Uneven Group Size Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 8,
            'groupCount' => 3,
            'groupSize' => 4,
        ));

        $signups = $this->generateRandomSignups(12);
        $this->client->addSignups($tournament['type'], $tournament['id'], $signups);
        $this->client->startCompetition($tournament['type'], $tournament['id']);

        $brackets = $this->client->getTournamentBrackets($tournament['id']);
        $this->assertCount(4, $brackets);

        $groupBracket = $brackets[0];
        $round = $groupBracket['rounds'][0];;

        foreach ($round['groups'] as $group)
        {
            foreach ($group['stages'] as $stage)
            {
                foreach ($stage['matches'] as $match)
                {
                    $this->client->updateMatch($tournament['type'], $tournament['id'], $match['id'], array(
                        'score' => rand(1, 100),
                        'scoreOpponent' => rand(101, 200),
                    ));
                }
            }
        }

        $roundUpdated = $this->client->getRound($tournament['type'], $tournament['id'], 1, $groupBracket['name']);
        $scoreEntries = array();

        foreach ($roundUpdated['groups'] as $group)
        {
            foreach ($group['standing'] as $standing)
            {
                $scoreEntries[] = $standing;
            }
        }

        usort($scoreEntries, function ($a, $b) {
            $fields = array('rank' => 'ASC', 'points' => 'DESC', 'score' => 'DESC', 'wins' => 'DESC');

            foreach ($fields as $field => $direction)
            {
                if ($a[$field] != $b[$field])
                {
                    if ($direction == 'ASC')
                    {
                        return $a[$field] - $b[$field];
                    }
                    else
                    {
                        return $b[$field] - $a[$field];
                    }
                }
            }

            return 0;
        });

        $expectedEntries = array_slice($scoreEntries, 0, 8);
        $expectedSignupIds = array();

        foreach ($expectedEntries as $entry)
        {
            $expectedSignupIds[$entry['signup']['id']] = true;
        }

        $brackets = $this->client->getTournamentBrackets($tournament['id']);
        $groupBracket = $brackets[0];
        $eliminationBracket = $brackets[1];

        $this->assertNotEmpty($groupBracket['finishedAt']);
        $this->assertCount(4, $eliminationBracket['rounds'][0]['matches']);

        foreach ($eliminationBracket['rounds'][0]['matches'] as $match)
        {
            $this->assertNotEmpty($match['signup']);
            $this->assertNotEmpty($match['signupOpponent']);

            $this->assertArrayHasKey($match['signup']['id'], $expectedSignupIds);
            $this->assertArrayHasKey($match['signupOpponent']['id'], $expectedSignupIds);

            unset($expectedSignupIds[$match['signup']['id']]);
            unset($expectedSignupIds[$match['signupOpponent']['id']]);
        }

        $this->assertEmpty($expectedSignupIds);
    }

    /**
     * Test that walkovers will be registered automagically when already decided previously in the tournament.
     *
     * This is in response to a bug reported by users at gamer.no, see e-mail from Tor-Steinar 16. jan 2014
     */
    public function testCompleteWalkoverDoubleElimination()
    {
        $this->markTestSkipped('Currently patches as a bug - see report from Erlend on Trello.');
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Complete Double Elimination Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 8,
            'playersOnTeam' => 1,
            'gameId' => 1,
        ));

        $signups = $this->generateRandomSignups($competition['teamCount'] - 1);
        $signupIds = $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $round = $this->client->getRound($competition['type'], $competition['id'], 1, 'winner');

        foreach ($round['matches'] as $match)
        {
            if (!$match['isWalkover'])
            {
                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => 9,
                    'scoreOpponent' => 2,
                ));
            }
        }

        $round = $this->client->getRound($competition['type'], $competition['id'], 2, 'winner');

        foreach ($round['matches'] as $match)
        {
            if (!$match['isWalkover'])
            {
                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => 9,
                    'scoreOpponent' => 2,
                ));
            }
        }

        $round = $this->client->getRound($competition['type'], $competition['id'], 1, 'loser');
        $isThereWalkover = false;

        foreach ($round['matches'] as $match)
        {
            if (!$match['isWalkover'])
            {
                $this->client->updateMatch($competition['type'], $competition['id'], $match['id'], array(
                    'score' => 9,
                    'scoreOpponent' => 2,
                ));
            }
            else
            {
                $isThereWalkover = true;
            }
        }

        $this->assertTrue($isThereWalkover);

        $round = $this->client->getRound($competition['type'], $competition['id'], 2, 'loser');

        foreach ($round['matches'] as $match)
        {
            $this->assertNotEmpty($match['signup']);
            $this->assertNotEmpty($match['signupOpponent']);
        }
    }

    /**
     * Test that the field "unfinishedMatches" gets populated as it should.
     */
    public function testPopulatedUnfinishedMatches()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Complete Double Elimination Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 8,
            'playersOnTeam' => 1,
        ));

        $rounds = $this->client->getRounds('tournament', $competition['id'], 'winner');

        foreach ($rounds as $round)
        {
            $this->assertEquals(count($round['matches']), $round['unfinishedMatches']);
        }
    }

    /**
     * Test that when we replace a signup, the proper matches gets updated (last unplayed match for the team being replaced)
     */
    public function testReplaceSignup()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Replace Signup Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 4,
            'playersOnTeam' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $this->client->startCompetition($competition['type'], $competition['id']);

        $brackets = $this->client->getTournamentBrackets($competition['id']);

        $this->assertNotEmpty($brackets[0]['rounds'][0]['matches'][0]['signup']);
        $this->assertNotEmpty($brackets[0]['rounds'][0]['matches'][0]['signupOpponent']);
        $this->assertNotEmpty($brackets[0]['rounds'][0]['matches'][1]['signup']);
        $this->assertNotEmpty($brackets[0]['rounds'][0]['matches'][1]['signupOpponent']);

        $this->client->updateMatch($competition['type'], $competition['id'], $brackets[0]['rounds'][0]['matches'][0]['id'], array(
            'score' => 9,
            'scoreOpponent' => 2,
        ));

        $this->client->updateMatch($competition['type'], $competition['id'], $brackets[0]['rounds'][0]['matches'][1]['id'], array(
            'score' => 2,
            'scoreOpponent' => 9,
        ));

        $brackets = $this->client->getTournamentBrackets($competition['id']);

        $this->assertNotEmpty($brackets[1]['rounds'][0]['matches'][0]['signup']);
        $this->assertNotEmpty($brackets[1]['rounds'][0]['matches'][0]['signupOpponent']);

        $originalSignupId = $brackets[1]['rounds'][0]['matches'][0]['signup']['id'];

        // lets replace a team, now that we have confirmed the current state of the tournament
        $signups = $this->generateRandomSignups(1);
        $signups = $this->client->addSignups($competition['type'], $competition['id'], $signups);
        $expectedSignupId = array_pop($signups);

        $result = $this->client->replaceSignup($competition['type'], $competition['id'], $originalSignupId, $expectedSignupId);

        $brackets = $this->client->getTournamentBrackets($competition['id']);

        // make sure that the previous scored entry is still kept intact
        $this->assertEquals($originalSignupId, $brackets[0]['rounds'][0]['matches'][0]['signupOpponent']['id']);
        $this->assertEquals($expectedSignupId, $brackets[1]['rounds'][0]['matches'][0]['signup']['id']);
    }

    /**
     * Test that we're able to put signups into certain slots if we want to.
     */
    public function testManualSignupPlacementBeforeStart()
    {
        $competition = $this->createNewTournamentForTests(array(
            'name' => 'Direct Placement Tournament Test',
            'tournamentType' => 'doubleelim',
            'teamCount' => 4,
            'playersOnTeam' => 1,
        ));

        $signups = $this->generateRandomSignups(4);
        $signupIds = $this->client->addSignups($competition['type'], $competition['id'], $signups);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $matches = $brackets[0]['rounds'][0]['matches'];

        $firstSignupId = array_shift($signupIds);
        $secondSignupId = array_pop($signupIds);

        $this->client->forceSignupIntoMatchSlot($competition['type'], $competition['id'], $matches[0]['id'], $firstSignupId, Pwned_Client::MATCH_SLOT_PRIMARY);
        $this->client->forceSignupIntoMatchSlot($competition['type'], $competition['id'], $matches[1]['id'], $secondSignupId, Pwned_Client::MATCH_SLOT_OPPONENT);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $this->assertEquals(1, $brackets[0]['roundCurrent']);
        $matches = $brackets[0]['rounds'][0]['matches'];

        $this->assertEquals($firstSignupId, $matches[0]['signup']['id']);
        $this->assertEmpty($matches[0]['signupOpponent']);
        $this->assertEquals($secondSignupId, $matches[1]['signupOpponent']['id']);
        $this->assertEmpty($matches[1]['signup']);

        $this->client->startCompetition($competition['type'], $competition['id']);

        $brackets = $this->client->getTournamentBrackets($competition['id']);
        $matches = $brackets[0]['rounds'][0]['matches'];

        $this->assertEquals($firstSignupId, $matches[0]['signup']['id']);
        $this->assertNotEmpty($matches[0]['signupOpponent']);
        $this->assertNotEquals($firstSignupId, $matches[0]['signupOpponent']['id']);
        $this->assertNotEquals($secondSignupId, $matches[0]['signupOpponent']['id']);

        $this->assertEquals($secondSignupId, $matches[1]['signupOpponent']['id']);
        $this->assertNotEmpty($matches[1]['signup']);
        $this->assertNotEquals($firstSignupId, $matches[1]['signup']['id']);
        $this->assertNotEquals($secondSignupId, $matches[1]['signup']['id']);
    }
}