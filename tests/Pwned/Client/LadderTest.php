<?php
class Pwned_Client_LadderTest extends Pwned_ClientTestAbstract
{
    /**
     * Test if we're able to create ladders
     */
    public function testLadderCreate()
    {
        $name = uniqid('Ladder ');

        $ladder = $this->createNewLadderForTests(array(
            'name' => $name,
            'description' => 'This is a magic ladder!',
            'scoringModel' => 'glicko2',
        ));

        $this->assertEquals('ladder', $ladder['type']);
        $this->assertEquals($name, $ladder['name']);
        $this->assertEquals('This is a magic ladder!', $ladder['description']);
        $this->assertEquals('glicko2', $ladder['scoringModel']['name']);
    }

    /**
     * Test optional values for ladders
     */
    public function testLadderCreateOptional()
    {
        $name = uniqid('Ladder ');

        $ladder = $this->createNewLadderForTests(array(
            'name' => $name,
            'description' => 'This is a magic ladder!',
            'scoringModel' => 'glicko2',
            'idleDeactivateDelay' => 14*24,
            'idlePenaltyDelay' => 7*24,
            'idlePenaltyInterval' => 2*24,
            'idlePenaltyPoints' => 30,
        ));

        $this->assertEquals('ladder', $ladder['type']);
        $this->assertEquals($name, $ladder['name']);
        $this->assertEquals('This is a magic ladder!', $ladder['description']);
        $this->assertEquals('glicko2', $ladder['scoringModel']['name']);
        $this->assertEquals(14*24, $ladder['idleDeactivateDelay']);
        $this->assertEquals(7*24, $ladder['idlePenaltyDelay']);
        $this->assertEquals(2*24, $ladder['idlePenaltyInterval']);
        $this->assertEquals(30, $ladder['idlePenaltyPoints']);
    }

    /**
     * Test adding team to ladder
     */
    public function testAddSignupsToLadder()
    {
        $ladder = $this->createNewLadderForTests(array(
            'name' => 'Ladder ' . uniqid(),
            'scoringModel' => 'glicko2',
        ));

        $signups = $this->generateRandomSignups(8);
        $this->client->addSignups($ladder['type'], $ladder['id'], $signups);
        $signupsServer = $this->client->getSignups($ladder['type'], $ladder['id']);

        $this->assertCount(8, $signupsServer);
    }

    /**
     * Test ladder scores after adding teams to ladder
     */
    public function testLadderRankingCreated()
    {
        $ladder = $this->createNewLadderForTests(array(
            'name' => 'Ladder ' . uniqid(),
            'scoringModel' => 'glicko2',
        ));

        $signups = $this->generateRandomSignups(8);
        $this->client->addSignups($ladder['type'], $ladder['id'], $signups);
        $ranking = $this->client->getLadderRanking($ladder['id']);

        $this->assertCount(8, $ranking);

        foreach ($ranking as $rank)
        {
            $this->assertNotEmpty($rank['score']);
            $this->assertEmpty($rank['scoreDelta']);
            $this->assertNotEmpty($rank['signup']);
        }
    }

    /**
     * Internal method to create a ladder across test methods.
     *
     * @param array $competitionInput To change any default values, supply better information here.
     * @return array A ladder created for further tests.
     */

    protected function createNewLadderForTests($competitionInput = null)
    {
        $competitionInputValues = array(
            'name' => 'Test Ladder ' . uniqid(),
        );

        if ($competitionInput)
        {
            $competitionInputValues = array_merge($competitionInputValues, $competitionInput);
        }

        return $this->client->createLadder($competitionInputValues);
    }
}
