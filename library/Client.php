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
        $this->debugEnabled = true;
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
        return $this->request($type . '/' . $competitionId, 'GET');
    }
    
    /**
     * Update a competition (start it, move it to next round, etc.)
     * 
     * Currently supported data array values:
     * 'round' => int: the current round - increase it with one to move to the next round, reduce it with one to move the competition a round back.
     * 'status' => string: the current state of the competition, possible values are 'ready', 'live', 'deleted', set it to live to start the competition, set it to delete to delete the competition.
     */
    public function updateCompetition($type, $competitionId, $competitionInfo)
    {
        return $this->request($type . '/' . $competitionId, 'POST', $competitionInfo);
    }
   
    
    /**
     * Retrieve a list of signups for a competition.
     * 
     * @param string $type
     * @param int $competitionId
     * @return array
     */
    
    public function getSignups($type, $competitionId)
    {
        return $this->request($type . '/' . $competitionId . '/signups', 'GET');
    }
    
    /**
     * Get the rounds to be played for a competition.
     * 
     * @param string $type
     * @param int $competitionId
     * @return array 
     */
    public function getRounds($type, $competitionId)
    {
        return $this->request($type . '/' . $competitionId . '/rounds', 'GET');
    }

    /**
     *
     * @param string $type
     * @param int $competitionId
     * @param int $roundNumber
     * @return array
     */
    public function getRound($type, $competitionId, $roundNumber)
    {
        return $this->request($type . '/' . $competitionId . '/rounds/' . $roundNumber, 'GET');
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
        return $this->request($type . '/' . $competitionId . '/signups', 'POST', $signups);
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
        return $this->request($type . '/' . $competitionId . '/signups/' . $signupId, 'DELETE');
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
        return $this->request($type . '/' . $competitionId . '/matches/' . $matchId, 'GET');
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
        return $this->request($type . '/' . $competitionId . '/matches/' . $matchId, 'POST', $matchData);
    }
    
    public function getLastError()
    {
        return $this->lastError;
    }

    public function setLastError($lastError)
    {
        $this->lastError = $lastError;
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
        
    protected function request($resource, $requestMethod = 'GET', $data = null)
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
        
        if ($data)
        {
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $url = $this->getURL() . $resource . $this->getURLArgumentString($resource, $requestMethod, $data);
        }
        else
        {
            $url = $this->getURL() . $resource . $this->getURLArgumentString($resource, $requestMethod, null);
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
        }
        
        if (!$result)
        {
            return null;
        }
        
        return $result['result'];
    }
    
    protected function getURLArgumentString($resource, $requestMethod, $data)
    {
        return '?publicKey=' . $this->getPublicKey() . 
               '&signature=' . self::createAPISignature($this->getPublicKey(), $this->getPrivateKey(), $requestMethod, $resource, $data);
    }
            
    static public function createAPISignature($publicKey, $privateKey, $requestMethod, $path, $data)
    {
        $data = $publicKey . '|' . $requestMethod . '|' . $path . '|' . $data;
        
        return hash_hmac('sha256', $data, $privateKey);
    }            
}