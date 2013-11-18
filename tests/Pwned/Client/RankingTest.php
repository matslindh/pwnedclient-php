<?php
class Pwned_Client_RankingTest extends Pwned_ClientTestAbstract
{
    /**
     * Test if we're able to create rankings
     */
    public function testRankingCreate()
    {
        $name = uniqid('Ranking ');

        $ranking = $this->createNewRankingForTests(array(
            'name' => $name,
            'description' => 'This is a ranking ranking rank!',
        ));

        $this->assertEquals($name, $ranking['name']);
        $this->assertEquals('This is a ranking ranking rank!', $ranking['description']);
    }

    /**
     * Test optional values for rankings
     */
    public function testRankingCreateOptional()
    {
        $name = uniqid('Ranking ');

        $ranking = $this->createNewRankingForTests(array(
            'name' => $name,
            'description' => 'This is a ranking ranking rank!',
            'rankingType' => 'all',
            'rankingValue' => 'time',
            'rankingDirection' => 'ascending',
        ));

        $this->assertEquals($name, $ranking['name']);
        $this->assertEquals('This is a ranking ranking rank!', $ranking['description']);
        $this->assertEquals('all', $ranking['rankingType']);
        $this->assertEquals('time', $ranking['rankingValue']);
        $this->assertEquals('ascending', $ranking['rankingDirection']);
    }

    /**
     * Add three entries to a ranking and get a score.
     */
    public function testRankingAddEntries()
    {
        $ranking = $this->createNewRankingForTests();

        $response = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 203',
                'remoteId' => '203',
            ),
            'value' => 1234566,
        ));

        $this->assertEquals(1, $response['positionInfo']['position']);
        $this->assertEquals(1, $response['positionInfo']['total']);

        $response = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 20013',
                'remoteId' => '20013',
            ),
            'value' => 1234567,
        ));

        $this->assertEquals(1, $response['positionInfo']['position']);
        $this->assertEquals(2, $response['positionInfo']['total']);

        $response = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 20012',
                'remoteId' => '20012',
            ),
            'value' => 123456,
        ));

        $this->assertEquals(3, $response['positionInfo']['position']);
        $this->assertEquals(3, $response['positionInfo']['total']);

        $response = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 20012',
                'remoteId' => '20012',
            ),
            'value' => 1234569392,
        ));

        $this->assertEquals(1, $response['positionInfo']['position']);
        $this->assertEquals(3, $response['positionInfo']['total']);

        $response = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 2001233',
                'remoteId' => '2001233',
            ),
            'value' => 1234569393,
        ));

        $this->assertEquals(1, $response['positionInfo']['position']);
        $this->assertEquals(4, $response['positionInfo']['total']);
    }

    /**
     * Remove a ranking entry and see that the best score / total number of entries updates
     */
    public function testRemoveRankingEntry()
    {
        $ranking = $this->createNewRankingForTests();

        $second = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 203',
                'remoteId' => '203',
            ),
            'value' => 1234566,
        ));

        $this->assertEquals(1, $second['positionInfo']['position']);
        $this->assertEquals(1, $second['positionInfo']['total']);

        $first = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 20013',
                'remoteId' => '20013',
            ),
            'value' => 1234567,
        ));

        $this->assertEquals(1, $first['positionInfo']['position']);
        $this->assertEquals(2, $first['positionInfo']['total']);

        $third = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 20012',
                'remoteId' => '20012',
            ),
            'value' => 123456,
        ));

        $this->assertEquals(3, $third['positionInfo']['position']);
        $this->assertEquals(3, $third['positionInfo']['total']);

        $this->client->removeRankingEntry($ranking['id'], $second['id']);

        $third_fetched = $this->client->getRankingEntry($ranking['id'], $third['id']);
        $first_fetched = $this->client->getRankingEntry($ranking['id'], $first['id']);

        $this->assertEquals($third_fetched['value'], $third['value']);
        $this->assertEquals($first_fetched['value'], $first['value']);
        $this->assertEquals(2, $third_fetched['positionInfo']['position']);
        $this->assertEquals(2, $third_fetched['positionInfo']['total']);
        $this->assertEquals(1, $first_fetched['positionInfo']['position']);
        $this->assertEquals(2, $first_fetched['positionInfo']['total']);
    }

   /**
     * Remove a ranking entry and see that the best score gets updated
     */
    public function testRemoveRankingEntryForUser()
    {
        $ranking = $this->createNewRankingForTests();

        $first = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 203',
                'remoteId' => '203',
            ),
            'value' => 1234566,
        ));

        $this->assertEquals(1, $first['positionInfo']['position']);
        $this->assertEquals(1, $first['positionInfo']['total']);

        $third = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 203',
                'remoteId' => '203',
            ),
            'value' => 12345,
        ));

        $this->assertEquals(2, $third['positionInfo']['position']);
        $this->assertEquals(1, $third['positionInfo']['total']);

        $second = $this->client->addRankingEntry($ranking['id'], array(
            'signup' => array(
                'name' => 'Foobar 203',
                'remoteId' => '203',
            ),
            'value' => 123456,
        ));

        $this->assertEquals(2, $second['positionInfo']['position']);
        $this->assertEquals(1, $second['positionInfo']['total']);

        $this->client->removeRankingEntry($ranking['id'], $first['id']);

        $best = $this->client->getRankingEntries($ranking['id'], array('remoteId' => 203));

        $this->assertEquals($second['id'], $best['id']);
        $this->assertEquals($second['value'], $best['value']);

        $this->client->removeRankingEntry($ranking['id'], $second['id']);

        $best = $this->client->getRankingEntries($ranking['id'], array('remoteId' => 203));
        $this->assertEquals($third['id'], $best['id']);
        $this->assertEquals($third['value'], $best['value']);
    }

    /**
     * Internal method to create a ranking across test methods.
     *
     * @param array $competitionInput To change any default values, supply better information here.
     * @return array A ranking created for further tests.
     */
    protected function createNewRankingForTests($competitionInput = null)
    {
        $competitionInputValues = array(
            'name' => 'Test Ranking ' . uniqid(),
        );

        if ($competitionInput)
        {
            $competitionInputValues = array_merge($competitionInputValues, $competitionInput);
        }

        return $this->client->createRanking($competitionInputValues);
    }
}
