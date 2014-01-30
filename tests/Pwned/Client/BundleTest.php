<?php
class Pwned_Client_BundleTest extends Pwned_ClientTestAbstract
{
    /**
     * Test if we can create a bundle
     */
    public function testCreateBundle()
    {
        $name = uniqid('Bundle Test ');

        $bundle = $this->client->createBundle(array(
            'name' => $name,
        ));

        $this->assertNotEmpty($bundle['id']);
        $this->assertEquals($name, $bundle['name']);
        $this->assertEmpty($bundle['competitions']);
    }

    /**
     * Test if we can retrieve a bundle
     */
    public function testGetBundle()
    {
        $name = uniqid('Bundle Test ');

        $bundle = $this->client->createBundle(array(
            'name' => $name,
        ));

        $bundleFetched = $this->client->getBundle($bundle['id']);
        $this->assertEquals($bundle['id'], $bundleFetched['id']);
        $this->assertEquals($name, $bundleFetched['name']);
        $this->assertEmpty($bundleFetched['competitions']);
    }

    public function testUpdateBundle()
    {
        $name = uniqid('Bundle Test ');
        $description = uniqid('Description Test ');

        $bundle = $this->client->createBundle(array(
            'name' => $name,
            'description' => $description,
        ));

        $bundleFetched = $this->client->getBundle($bundle['id']);
        $this->assertEquals($bundle['id'], $bundleFetched['id']);
        $this->assertEquals($name, $bundleFetched['name']);
        $this->assertEquals($description, $bundleFetched['description']);

        $name = uniqid('Bundle Test ');
        $description = uniqid('Description Test ');

        $bundle = $this->client->updateBundle($bundle['id'], array(
            'name' => $name,
            'description' => $description,
        ));

        $bundleFetched = $this->client->getBundle($bundle['id']);
        $this->assertEquals($bundle['id'], $bundleFetched['id']);
        $this->assertEquals($name, $bundleFetched['name']);
        $this->assertEquals($description, $bundleFetched['description']);
    }

    /**
     * Test if we can create associate tournaments, rankings and leagues with our bundle
     */
    public function testCreateBundleWithCompetitions()
    {
        $name = uniqid('Bundle Test ');

        $bundle = $this->client->createBundle(array(
            'name' => $name,
        ));

        $this->assertNotEmpty($bundle['id']);

        // leagues are tested elsewhere, we just need one ... JUST ONE ...
        $league = $this->client->createLeague(array(
            'leagueType' => 'league',
            'teamCount' => 8,
            'scoringModelId' => 1,
            'bundleId' => $bundle['id'],
        ));

        $this->assertNotEmpty($league['id']);

        // tournaments are also tested elsewhere
        $tournament = $this->createNewTournamentForTests(array(
            'bundleId' => $bundle['id'],
        ));

        // guess where rankings are actually tested? elsewhere!
        $ranking = $this->client->createRanking(array(
            'name' => 'Ranking Bundle Test #' . $bundle['id'],
            'bundleId' => $bundle['id'],
        ));

        // ladders .. i'm done.
        $ladder = $this->client->createLadder(array(
            'name' => 'Test Ladder ' . uniqid(),
            'scoringModel' => 'glicko2',
            'bundleId' => $bundle['id'],
        ));

        $bundleFetched = $this->client->getBundle($bundle['id']);

        $this->assertNotEmpty($bundleFetched['competitions']);
        $this->assertEquals($bundleFetched['competitions'][0]['id'], $league['id']);
        $this->assertEquals($bundleFetched['competitions'][1]['id'], $tournament['id']);
        $this->assertEquals($bundleFetched['competitions'][2]['id'], $ranking['id']);
        $this->assertEquals($bundleFetched['competitions'][3]['id'], $ladder['id']);
    }
}