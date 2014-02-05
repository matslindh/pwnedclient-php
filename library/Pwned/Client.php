<?php
class Pwned_Client
{
    protected $url;
    protected $privateKey;
    protected $publicKey;
    protected $lastError;
    protected $errors = array();

    protected $debugValues = array();
    protected $debugEnabled = false;
    protected $errorCallback = null;

    public function __construct($url, $publicKey, $privateKey)
    {
        $this->setURL($url);
        $this->setPrivateKey($privateKey);
        $this->setPublicKey($publicKey);
    }

    public function getURL()
    {
        return $this->url;
    }

    public function setURL($url)
    {
        if (substr($url, -1) != '/')
        {
            $url .= '/';
        }

        $this->url = $url;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * If debugging is turned on (enableDebugging), retrieve a copy of the requests made and the raw responses from the server.
     *
     * @return array
     */
    public function getDebugValues()
    {
        return $this->debugValues;
    }

    /**
     * Get the callback function which is called if an error occurs
     *
     * @return function
     */
    public function getErrorCallback()
    {
        return $this->errorCallback;
    }

    /**
     * Set the callback to use if an error occurs.
     *
     * @param function $errorCallback
     */
    public function setErrorCallback($errorCallback)
    {
        $this->errorCallback = $errorCallback;
    }

    /**
     * If debugging is enabled, retrieve a string version of the requests to the server and responses from the server.
     *
     * @return string
     */
    public function getDebugValuesForReport()
    {
        return json_encode($this->debugValues);
    }

    /**
     * If debugging is enabled, write the contents of the requests and responses in this session to a file.
     *
     * @param string $file The file we should write our debug information to.
     * @return array
     */
    public function writeDebugValuesForReport($file)
    {
        return file_put_contents($file, json_encode($this->debugValues));
    }

    protected function addDebugValue($context, $debugValue)
    {
        $this->debugValues[] = array(
            'context' => $context,
            'value' => $debugValue,
            'errors' => $this->getErrors(),
        );
    }

    public function isDebuggingEnabled()
    {
        return $this->debugEnabled;
    }

    public function enableDebugging()
    {
        $this->debugEnabled = true;
    }

    public function disableDebugging()
    {
        $this->debugEnabled = false;
    }

    /**
     * Create a Tournament
     *
     * Supported entries:
     * 'name' => string: The name of the tournament
     * 'tournamentType' => string: The type of tournament to create - 'singleelim' or 'doubleelim' are valid values.
     * 'teamCount' => int: The number of teams (will be rounded up to the nearest 2^x value (4,8,16,32,.. etc).
     *
     * Optional entries:
     * 'playersOnTeam' => int: The number of players on each team in the tournament
     * 'gameId' => int: The integer id of the game type of the tournament
     * 'description' => string: The description of the tournament; a subset of HTML is supported and is purified after being submitted.
     * 'groupCount' => int: the number of groups in the preliminary stage
     * 'groupSize' => int: the size of the groups in the preliminary stage
     * 'quickProgress' => boolean: Wether the tournament should use the "quick progress" format where teams are moved to the next round as soon as a result is entered. This defaults to true.
     */
    public function createTournament($tournamentInfo)
    {
        return $this->request('tournaments', 'POST', $tournamentInfo);
    }

    /**
     * Create a League
     *
     * Supported entries:
     * 'leagueType' => string: The type of league (league or championship)
     * 'teamCount' => int: The max number of teams allowed into the league.
     * 'scoringModelId' => int: The scoring model to use for the tournament. Query /leagues/scoringmodels for available models.
     *
     * Optional entries:
     * 'name' => string: The name of the league, if not present a name will be generated for you
     * 'language' => string: The default language to present the league in (valid values are currently norwegian, english)
     * 'description' => string: The description of the league; a subset of HTML is supported and is purified after being submitted.
     * 'gameId' => int: The integer id of the game type of the league
     * 'playersOnTeam' => int: The number of players on each team in the league
     * 'countryId' => int: Which country this league is in / assigned to
     * 'bundleId' => int: A reference to a competition bundle to add the league / leagues to
     * 'leagueCount' => int: Generate a set of leagues by using a leagueCount > 1. leagueCount = 4 will generate four different leagues. You probably want to use this together with bundleId.
     */
    public function createLeague($leagueInfo)
    {
        return $this->request('leagues', 'POST', $leagueInfo);
    }

   /**
     * Create a Ladder
     *
     * Supported entries:
     * 'name' => string: The title of the ladder
     * 'scoringModel' => string: The scoring model of the ladder (valid values: glicko2, elo)
     * 'gameId' => int: The integer id of the game type of the ladder
     * 'playersOnTeam' => int: The number of players on each team in the ladder
     *
     * Optional entries:
     * 'description' => string: The description of the ladder; a subset of HTML is supported and is purified after being submitted.
     */
    public function createLadder($ladderInfo)
    {
        return $this->request('ladders', 'POST', $ladderInfo);
    }

   /**
     * Create a Ranking
     *
     * Supported entries:
     * 'name' => string: The title of the ladder
     *
     * Optional entries:
     * 'description' => string: The description of the ladder; a subset of HTML is supported and is purified after being submitted.
     * 'gameId' => int: The integer id of the game type of the ladder
     * 'playersOnTeam' => int: The number of players on each team in the ladder
     */
    public function createRanking($rankingInfo)
    {
        return $this->request('rankings', 'POST', $rankingInfo);
    }

    /**
     * Get base information for a competition.
     *
     * @param string $type
     * @param int $competitionId
     * @return type array
     */
    public function getCompetition($type, $competitionId)
    {
        return $this->request($type . 's/' . $competitionId, 'GET');
    }

    /**
     * Retrieve information about a league. Alias for getCompetition('league', ..);
     *
     * @param int $leagueId ID of the league to retrieve.
     * @return type array
     */
    public function getLeague($leagueId)
    {
        return $this->getCompetition('league', $leagueId);
    }

    /**
     * Retrieve information about a tournament. Alias for getCompetition('tournament', ..);
     *
     * @param int $tournamentId ID of the tournament to retrieve.
     * @return type array
     */
    public function getTournament($tournamentId)
    {
        return $this->getCompetition('tournament', $tournamentId);
    }

    /**
     * Retrieve information about a league. Alias for getCompetition('ladder', ..);
     *
     * @param int $ladderId ID of the ladder to retrieve.
     * @return type array
     */
    public function getLadder($ladderId)
    {
        return $this->getCompetition('ladder', $ladderId);
    }

    /**
     * Update a competition (start it, move it to next round, etc.)
     *
     * Currently supported data array values:
     *
     * Optional entries:
     * 'name' => string: the name of the competition
     * 'playersOnTeam' => int: the number of players on each team ([1, 1024])
     * 'gameId' => int: the game the competition is for
     * 'description' => string: The text featured as the description of the competition. Supports a safe subset of HTML.
     * 'round' => int: the current round - increase it with one to move to the next round, reduce it with one to move the competition a round back.
     * 'status' => string: the current state of the competition, possible values are 'ready', 'live', 'deleted', set it to live to start the competition, set it to delete to delete the competition.
     *
     * Optional entries for tournaments (these may only be changed before starting the tournament):
     * 'tournamentType' => string: singleelim or doubleelim
     * 'teamCount' => int: the number of teams / players to compete in the tournament (after the optional group stage).
     * 'groupSize' => int: the number of teams/players in each group stage if a preliminary group stage is requested [2,16]
     * 'groupCount' => int: the number of groups in an optional preliminary group stage (both groupSize and groupCount are required together) [2,64]
     *
     * Optional entries for leagues (may only be changed before starting a league):
     * 'teamCount' => int: the number of teams / players to compete in the league
     */
    public function updateCompetition($type, $competitionId, $competitionInfo)
    {
        return $this->request($type . 's/' . $competitionId, 'POST', $competitionInfo);
    }

    /**
     * Helper function to start a competition
     */
    public function startCompetition($type, $competitionId)
    {
        return $this->updateCompetition($type, $competitionId, array('status' => 'live'));
    }

    /**
     * Get the brackets for a tournament (a bracket will contain rounds, and will usually be 'group', 'winner', 'loser', 'final', depending on
     * the type of tournament.
     */
    public function getTournamentBrackets($tournamentId)
    {
        return $this->request('tournaments/' . $tournamentId . '/brackets');
    }

    /**
     * Get the rounds to be played for a competition.
     *
     * @param string $type
     * @param int $competitionId
     * @return array
     */
    public function getRounds($type, $competitionId, $tournamentBracket = null)
    {
        return $this->request($type . 's/' . $competitionId . '/' . ($tournamentBracket ? $tournamentBracket . '/' : '') . 'rounds', 'GET');
    }

    /**
     *
     * @param string $type
     * @param int $competitionId
     * @param int $roundNumber
     * @return array
     */
    public function getRound($type, $competitionId, $roundNumber, $bracket = '')
    {
        return $this->request($type . 's/' . $competitionId . '/' . ($bracket ? $bracket . '/' : '') . 'rounds/' . $roundNumber, 'GET');
    }

    /**
     * Retrieve a list of signups for a competition.
     *
     * @param string $type
     * @param int $competitionId
     * @param string $fetchMode 'normal' to retrieve accepted and not on waiting list, 'waiting' to retrieve accepted and on waiting list, 'notaccepted' to fetch not accepted signups and 'all' to retrieve all signed up teams regardless of state.
     * @return array
     */
    public function getSignups($type, $competitionId, $fetchMode = null)
    {
        return $this->request($type . 's/' . $competitionId . '/signups' . ($fetchMode ? '/' . $fetchMode : ''), 'GET');
    }

    /**
     * Submit new signups for a competition.
     *
     * Accepts an array of signups (so you can submit more than one signup in one call).
     * Each element in the array require the 'name' element, all other are optional.
     *
     * Elements in each associative array:
     * 'name' => string: The name of the signup
     * 'hasServer' => boolean: Wether this signup has access to a server (true)
     * 'isAccepted' => boolean: Wether this signup is accepted into the tournament (true)
     * 'onWaitingList' => boolean: Wether this signup should be placed on the waiting list (false) (if there's no available spots, the signup will be placed on the waiting list)
     * 'contact' => string: Contact information (irc nick/xbox live name/psn account/steam id) for the signup
     * 'seeding' => unsigned integer: The seeding of this signup - this is currently not used when setting up the matches (none)
     * 'clanId' => unsigned integer: a clan id on the site to assign the sign up to
     * 'remoteId' => unsigned big integer (64-bit): An id that will be included in any response containing a signup. Use this to associate a signup to a local account/team.
     *
     * @param string $type
     * @param int $competitionId
     * @param array $signups
     * @return
     */
    public function addSignups($type, $competitionId, $signups)
    {
        return $this->request($type . 's/' . $competitionId . '/signups', 'POST', $signups);
    }

    /**
     * Remove an already added signup by the pwned.no signup id (the 'id' field of any signup for this competition).
     *
     * @param string $type
     * @param int $competitionId
     * @param int $signupId
     * @return boolean
     */
    public function removeSignup($type, $competitionId, $signupId)
    {
        return $this->request($type . 's/' . $competitionId . '/signups/' . $signupId, 'DELETE');
    }

    /**
     * Replace one signup with another signup - the replaceWith signup will be placed in all non-completed matches
     * for the original signup.
     *
     * @param string $type
     * @param int $competitionId
     * @param int $signupId The id of original signup - the signup to be replaced with another
     * @param int $replaceWithSignupId The id of the signup which should replace the original signup
     */
    public function replaceSignup($type, $competitionId, $signupId, $replaceWithSignupId)
    {
        return $this->request($type . 's/' . $competitionId . '/signups/replace', 'POST', array(
            'replaceSignupId' => $signupId,
            'replaceWithSignupId' => $replaceWithSignupId,
        ));
    }

    /**
     * Get information about a particular match.
     *
     * @param string $type
     * @param int $competitionId
     * @param int $matchId
     * @return array
     */
    public function getMatch($type, $competitionId, $matchId)
    {
        return $this->request($type . 's/' . $competitionId . '/matches/' . $matchId, 'GET');
    }

    /**
     * Update a match (usually the result).
     *
     * matchData is an associative array, containing the following fields (all optional):
     * 'score' => int: The score of the home team (identified by signup)
     * 'scoreOpponent' => int: The score of the away team (identified by signupOpponent)
     * 'walkover' => string: can be either 'signup' or 'signupOpponent' and will register a walkover win to either signup
     *
     * @param string $type
     * @param int $competitionId
     * @param int $matchId
     * @param array $matchData
     * @return boolean
     */
    public function updateMatch($type, $competitionId, $matchId, $matchData)
    {
        return $this->request($type . 's/' . $competitionId . '/matches/' . $matchId, 'POST', $matchData);
    }

    /**
     * Get a list of the available tournament bracket types
     *
     * @return array<array>
     */
    public function getTournamentTemplates()
    {
        return $this->request('tournaments/templates', 'GET');
    }

    /**
     * Move tournament walkovers (teams missing opponents) to the next round.
     *
     * @return array The number of walkovers performed (with the key 'count')
     */
    public function moveTournamentWalkoversToNextRound($type, $competitionId, $roundId)
    {
        return $this->request($type . 's/' . $competitionId . '/rounds/' . $roundId . '/handlewalkovers', 'POST');
    }

    /**
     * Get the table / standings for a league.
     *
     * @return array<array> Returns an array of arrays, each array containing information about the table position.
     */
    public function getLeagueTable($leagueId)
    {
        return $this->request('leagues/' . $leagueId . '/table', 'GET');
    }

    /**
     * Get a list of all the available scoring models for leagues and championships
     *
     * @return array<array>
     */
    public function getLeagueScoringModels($type = null)
    {
        return $this->request('leagues/scoringmodels' . ($type ? '/' . $type : ''), 'GET');
    }

    /**
     * Retrieve a specific league scoring model
     *
     * @param int $scoringModelId The ID of the Scoring Model to fetch
     */
    public function getLeagueScoringModel($scoringModelId)
    {
        return $this->request('leagues/scoringmodels/' . ((int) $scoringModelId));
    }

    /**
     * Create a new scoring model.
     *
     * A scoring model can be configured with the following values.
     *
     * Required
     *  'type' => string: Either 'league' or 'championship', depending on what kind of league this scoring model applies to.
     *  'name' => string: Display name of the scoring model, or a key to identify the scoring model in your own UI.
     *
     * Optional
     *  'winPoints' => int: If leagueType is league, the number of points to give to a team/player for winning a match.
     *  'drawPoints' => int: If leagueType is league, the number of points to give to both teams/players when playing a draw in a match.
     *  'lossPoints' => int: If leagueType is league, the number of points to give to a team/player for losing a match (usually zero).
     *  'description' => string: A textual description of the scoring model. Can be applied together with name, where name is a key string and description contains a complete description / real name of the scoring model.
     *  'positionPoints' => array: An associative array with each position as key and the points given to that position as value. I.e. array( 1 => 30, 2 => 15, 3 => 5 );
     *
     * @param array $scoringModelData Information about the scoring model to create.
     * @return array Returns the created scoring model.
     */
    public function createLeagueScoringModel($scoringModelData)
    {
        return $this->request('leagues/scoringmodels', 'POST', $scoringModelData);
    }

    /**
     * Update an existing scoring model.
     *
     * See createLeagueScoringModel for a description of possible values.
     *
     * @param int $scoringModelId Id of the scoring model we're updating.
     * @param array $scoringModelData Information to update for the scoring model.
     * @return array Returns the updated scoring model.
     */
    public function updateLeagueScoringModel($scoringModelId, $scoringModelData)
    {
        return $this->request('leagues/scoringmodels/' . ((int) $scoringModelId), 'POST', $scoringModelData);
    }

    /**
     * Remove an existing scoring model (set the scoring model as not active, as it might still be referenced by existing leagues).
     *
     * @param int $scoringModelId Id of the scoring model to remove.
     */
    public function deleteLeagueScoringModel($scoringModelId)
    {
        return $this->request('leagues/scoringmodels/' . ((int) $scoringModelId), 'DELETE');
    }

    /**
     * Submit an updated (or fresh) result for a particular round in a league (only valid for championships).
     *
     * The results array is structured as an array of arrays, with each internal array keeping information
     * about one team / player / signup competing in the championship.
     *
     * A result entry consists of:
     *  'position' => int: The end position of this result entry [1 - teamCount]
     *  'signupId' => int: The signup id this positional entry refers to
     *  'score' => int: The score associated with this entry (i.e. if the player got 35 points in this round, submit this value as 35).
     *
     * @param int $leagueId The id of the league we're submitting results for
     * @param int $roundNumber The round number to update [1, roundCount]
     * @param array $results An array containing the results to set for this round.
     * @return boolean
     */
    public function updateLeagueRoundResults($leagueId, $roundNumber, $results)
    {
        return $this->request('leagues/' . $leagueId . '/rounds/' . $roundNumber . '/results', 'POST', $results);
    }

    /**
     * Create a new bundle - a container if you want to group several competitions (for divisions, playoffs, etc.).
     *
     * The allowed keys in $settings:
     *
     * Required:
     *  'name': The name of the Bundle
     *
     * Optional:
     *  'description': A textual description of the bundle, included with every response.
     *
     * @param $settings An associative array with the settings for the bundle
     * @return array|null An array describing the created bundle - null on error.
     */
    public function createBundle($settings)
    {
        return $this->post('bundles', $settings);
    }

    /**
     * Get a bundle.
     *
     * Retrieves information about a bundle and the associated competitions.
     *
     * @param $bundleId Integer identifying the bundle to retrieve.
     * @return array|null Either an array describing the bundle or null if not found.
     */
    public function getBundle($bundleId)
    {
        return $this->get('bundles/' . $bundleId);
    }

    /**
     * Update a bundle.
     *
     * Retrieves information about a bundle and the associated competitions.
     *
     * The settings array can contain the following keys, all optional:
     *  'name': The name of the Bundle
     *  'description': A textual description of the bundle, included with every response.
     *
     * @param $bundleId Integer identifying the bundle to retrieve.
     * @param $settings array An associative array with the settings to update for the bundle.
     * @return array|null Either an array describing the bundle or null if not found.
     */
    public function updateBundle($bundleId, $settings)
    {
        return $this->post('bundles/' . $bundleId, $settings);
    }

    /**
     * Get the current ranking list for a ladder
     *
     * @param int $ladderId The id of the ladder to retrieve the current ranking for
     * @param int $offset The offset of ranking list to fetch
     * @param int $hits How many elements to retrieve of the ranking list
     */
    public function getLadderRanking($ladderId, $offset = 0, $hits = 150)
    {
        return $this->request('ladders/' . $ladderId . '/ranking', 'GET');
    }

    /**
     * Get ranking details for a specific ranking
     *
     * @param int $rankingId The id of the ranking to retrieve
     * @return array Information about the ranking identified by the id; null if not found.
     */
    public function getRanking($rankingId)
    {
        return $this->getCompetition('ranking', $rankingId);
    }

    /**
     * Add a score entry to a ranking.
     *
     * The entity behind the score entry can be identified by using `signupId`, `remoteId` or
     * by supplying a signup element under the `signup` key.
     *
     * @param int $rankingId The id of the ranking to submit a result entry for
     * @param array $entryData An associative array with the ranking entry data. Value is the only required key.
     * @return array For unique rankings, returns the current ranking information, for all entry rankings,
     *               returns the ranking information of the this entry.
     */
    public function addRankingEntry($rankingId, $entryData)
    {
        return $this->request('rankings/' . $rankingId . '/entries', 'POST', $entryData);
    }

    /**
     * Remove a score from a ranking.
     *
     * @param int $rankingId The ID of the ranking to remove the score from
     * @param int $rankingScoreId The ranking entry to be removed.
     * @return type
     */
    public function removeRankingEntry($rankingId, $rankingScoreId)
    {
        return $this->request('rankings/' . $rankingId . '/entries/' . $rankingScoreId, 'DELETE');
    }

    /**
     * Get a specific ranking entry by id
     *
     * @param int $rankingId The ID of the ranking to remove the score from
     * @param int $rankingScoreId The ranking entry to be removed.
     * @return array|null
     */
    public function getRankingEntry($rankingId, $rankingScoreId)
    {
        return $this->request('rankings/' . $rankingId . '/entries/' . $rankingScoreId, 'GET');
    }

    /**
     * Retrieve ranking entries from a specific ranking.
     *
     * @param int $rankingId The id of the ranking to retrieve entries from
     * @param array $arguments An array allowing you to filter for a specific remoteId or signupId (['remoteId' => '123'])
     * @return type array
     */
    public function getRankingEntries($rankingId, $arguments = array())
    {
        return $this->get('rankings/' . $rankingId . '/entries', $arguments);
    }

    /**
     * Get a list of ranking entries around a specific remoteId. This will fetch the best
     * ranking entry for the given remoteId and $hits number of elements around it (making
     * it $hits element in total).
     *
     * @param int $rankingId
     * @param int $remoteId
     * @param int $hits
     * @return array
     */
    public function getRankingEntriesAroundRemoteId($rankingId, $remoteId, $hits = 5)
    {
        return $this->get('rankings/' . $rankingId . '/entries', array('aroundRemoteId' => $remoteId, 'hits' => $hits));
    }

    /**
     * Get a list of the configured games available and their metadata.
     *
     * @return array<array>
     */
    public function getGames()
    {
        return $this->request('games', 'GET');
    }

    /**
     * Get a list of the available countries and their settings.
     *
     * @return array<array>
     */
    public function getCountries()
    {
        return $this->request('countries', 'GET');
    }

    /**
     * Ping the API server to see if it's alive and that your keys are working
     *
     * @return string Returns 'pong' if successful, empty otherwise.
     */
    public function ping()
    {
        return $this->request('ping', 'GET');
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function setLastError($lastError)
    {
        $this->lastError = $lastError;
    }

    public function getLastErrorReason()
    {
        if ($this->lastError)
        {
            return $this->lastError['reason'];
        }

        return null;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function addError($error)
    {
        $this->setLastError($error);
        $this->errors[] = $error;
    }

    protected function get($resource, $args = array())
    {
        return $this->request($resource, 'GET', null, $args);
    }

    protected function post($resource, $data)
    {
        return $this->request($resource, 'POST', $data);
    }

    protected function request($resource, $requestMethod = 'GET', $data = null, $getArgs = array())
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_USERAGENT => __CLASS__,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
        ));

        if ($requestMethod != 'GET')
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
        }

        $url = false;
        $urlArguments = '';

        if ($getArgs)
        {
            $urlArguments .= http_build_query($getArgs, '', '&', PHP_QUERY_RFC3986);
        }

        if ($data)
        {
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $url = $this->getURL() . $resource . '?' . ($urlArguments ? $urlArguments . '&' : '') . $this->getURLArgumentString($resource, $requestMethod, $data, $urlArguments);
        }
        else
        {
            $url = $this->getURL() . $resource . '?' . ($urlArguments ? $urlArguments . '&' : '') . $this->getURLArgumentString($resource, $requestMethod, null, $urlArguments);
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        if ($this->isDebuggingEnabled())
        {
            $this->addDebugValue('request', array(
                'requestMethod' => $requestMethod,
                'url' => $url,
                'data' => $data,
            ));
        }

        $content = curl_exec($ch);

        $result = json_decode($content, true);

        if ($result && $result['error'])
        {
            $this->addError($result['error']);
        }
        else if ($result)
        {
            $this->setLastError(null);
        }
        else
        {
            $this->addError(array('key' => 'invalid_json_structure_returned', 'reason' => 'The content returned from the server wasn\'t valid JSON.'));
        }

        if ($this->isDebuggingEnabled())
        {
            $this->addDebugValue('response', array(
                'requestMethod' => $requestMethod,
                'url' => $url,
                'data' => $content,
            ));

            if ($this->getLastError() && $this->getErrorCallback())
            {
                $debugValues = $this->getDebugValues();
                $callback = $this->getErrorCallback();
                $callback($debugValues[count($debugValues) - 2], $debugValues[count($debugValues) - 1]);
            }
        }

        if (!$result)
        {
            return null;
        }

        return $result['result'];
    }

    protected function getURLArgumentString($resource, $requestMethod, $data, $queryString)
    {
        return 'publicKey=' . $this->getPublicKey() .
               '&signature=' . self::createAPISignature($this->getPublicKey(), $this->getPrivateKey(), $requestMethod, $resource, $data, $queryString);
    }

    static public function createAPISignature($publicKey, $privateKey, $requestMethod, $path, $data, $queryString)
    {
        $data = $publicKey . '|' . $requestMethod . '|' . $path . '|' . $queryString . '|' . $data;

        return hash_hmac('sha256', $data, $privateKey);
    }
}
