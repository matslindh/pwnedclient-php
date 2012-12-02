<?php
class Pwned_ClientTestAbstract extends PHPUnit_Framework_TestCase
{
    /**
     * Set up a pwned client for internal re-use for each test.
     */
    public function setUp()
    {
        $this->client = new Pwned_Client($GLOBALS['PWNED_API_URL'], $GLOBALS['PWNED_API_PUBLIC_KEY'], $GLOBALS['PWNED_API_PRIVATE_KEY']);
        
        if (isset($GLOBALS['PWNED_CLIENT_DEBUG']))
        {
            $this->client->setErrorCallback(function ($request, $response) {
                var_dump($request);
                var_dump($response);
            });

            $this->client->enableDebugging();
        }
    }
    
    /**
     * Internal method to create a tournament across test methods.
     * 
     * @param array $competitionInput If we should create a tournament with specific values, supply the information here.
     * @return array An example tournament / competition created for further testing.
     */
    protected function createNewTournamentForTests($competitionInput = null)
    {
        $competitionInputValues = array(
            'name' => 'Test Tournament #123',
            'gameId' => 3,
            'playersOnTeam' => 5,
            'template' => 'singleelim16',
            'countryId' => 1,
        );        
        
        if ($competitionInput)
        {
            $competitionInputValues = array_merge($competitionInputValues, $competitionInput);
        }
        
        return $this->client->createTournament($competitionInputValues);
    }
    
    /**
     * Generate a random set of signups.
     */
    protected function generateRandomSignups($count)
    {
        $signups = array();
        
        for ($i = 0; $i < $count; $i++)
        {
            $signups[] = array(
                'name' => 'SignupTest ' . uniqid(),
                'hasServer' => true,
                'isAccepted' => true,
                'onWaitingList' => false,
                'contact' => 'Contact ' . uniqid(),
                'remoteId' => rand(1, 100000000),
            );
        }
        
        return $signups;
    }
}