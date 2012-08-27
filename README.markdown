# pwnedclient-php
A PHP Client for the API of pwned.no

## Requirements
The client uses the curl php module for all its requests. A PSR-0 compatible 
autoloader should be able to autoload the client.

## Installation
Simply drop Pwned/Client.php in the one of the base paths for your 
autoloader and watch the world come alive.

In its current form you can also do a simple require on the file,
as it doesn't have any external dependencies.

## Usage

Please see [the unit tests in ClientTest.php](blob/master/tests/Pwned/ClientTest.php) for useful examples of how to use the library.

### Create a Pwned client instance
```php
$pwned = new Pwned_Client('<url>', '<publicKey>', '<privateKey>');
```

### Create a tournament
```php
$competitionDefinition = array(
    'name' => 'Test Tournament #123',
    'gameId' => 3,
    'playersOnTeam' => 5,
    'template' => 'singleelim16',
    'countryId' => 1,
);
        
$competition = $pwned->createTournament($competitionDefinition);
```

The tournament will be createad as a tournament with 16 teams, single elimination.

#### Required keys for all competitions
* 'name' => string: The name of the tournament
* 'gameId' => int: The id of the game used for the tournament. A list of available games can be retrieved by calling ```getGames()```.
* 'playersOnTeam' => int: The number of players on each team in the tournament (use 1 to indicate a player vs player tournament).
* 'countryId' => int: Which country this tournament is in / assigned to. Retrieve a list of possible countries by calling ```getCountries()```.

#### Required keys for tournaments
* 'template' => string: The template to use for the bracket setup - the type of tournament to create. Retrieve a list of possible templates by calling ```getTournamentTemplates()```.

#### Optional keys
* 'language' => string: The default language to present the tournament in (valid values are currently norwegian, english).
* 'description' => string: The description of the tournament; a subset of HTML is supported and is purified after being submitted.
* 'groupCount' => int: the number of groups in the preliminary stage. Both groupCount and groupStage has to be present to have any effect.
* 'groupSize' => int: the size of each group in the preliminary stage. Both groupCount and groupStage has to be present to have any effect.
* 'quickProgress' => boolean: Wether the tournament should use the "quick progress" format where teams are moved to the next round as soon as a result is entered.

### Retrieve information about a competition
```php
$info = $pwned->getCompetition('tournament', <competitionId>);
```

This will return an associative array containing information about the tournament represented by the tournamentId (an integer).

### Sign up one or more teams for a competition
```php
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
);

$pwned->addSignups('tournament', <competitionId>, $signups);
```

Adding signups to a competition accepts a list of signups, making it possible to dump the complete list of signups in one API call if needed.

#### Required keys for each signup
* 'name' => string: The name of the team / player for this signup

#### Optional keys for each signup
* 'hasServer' => boolean: Wether this signup has access to a server (default: true)
* 'isAccepted' => boolean: Wether this signup is accepted into the tournament (default: true)
* 'onWaitingList' => boolean: Wether this signup should be placed on the waiting list (default: false) (if there's no available spots, the signup will be placed on the waiting list)
* 'contact' => string: Contact information (irc nick/xbox live name/psn account/steam id) for the signup (default: null)
* 'seeding' => unsigned integer: The seeding of this signup - this is currently not used when setting up the matches (default: null)
* 'clanId' => unsigned integer: a clan id on the site to assign the sign up to (default: null)
* 'remoteId' => unsigned big integer (64-bit): An id that will be included in any response containing a signup. Use this to associate a signup to a local account/team. (default: null)

### Get all competition rounds
```php
$pwned->getRounds('tournament', <competitionId>);
```

A competition round is a collection of stages that equals "one step" in the tournament. Each round may contain several stages (a group round (round robin) will contain one stage for each leg of the league stage), although they usually only contain one stage with a set of matches (for all regular tournament elimination rounds).

### Get a specific competition round
```php
$pwned->getRound('tournament', <competitionId>, <roundNumber>);
```

Returns information about a particular round for the competition identified by ```competitionId```. ```roundNumber``` can be between 1 and the total number of rounds in the tournament (singleelim4 will have two rounds, singleelim8 will have three and so on).

### Retrieve signed up teams for a competition
```php
$signups = $pwned->getSignups('tournament', <competitionId>[, <fetchmode>])
```

Returns an array of associative arrays describing the teams that have been signed up for the competition. 

#### Optional arguments
* fetchMode: A string indicating wether to return just the accepted entries ('normal', default), accepted and on waiting list ('waiting'), not accepted ('notaccepted') and all signed up teams regardless of state ('all')

### Remove signup from a competition
```php
$pwned->removeSignup('tournament', <competitionId>, <signupId>);
```

Removes the signup identified by the id from the competition. The signupId is included in the elements returned from ```getSignups()```.

### Replace a signup with another signup
```php
$pwned->replaceSignup('tournament', <competitionId>, <signupId>, <replaceWithSignupId>);
```

Replacing an existing signup with a new signup requires you to first add the new signup to the competition (placing it on the waiting list as the competition should be full already), before requesting that an exchange is made.

Both ```signupId``` and ```replaceWithSignupId``` references the ids returned from ```getSignups()```, and will place the team/player in ```replaceWithSignupId``` in all undecided matches for ```signupId``` in the competition.

### Get information about a particular match
```php
$pwned->getMatch('tournament', <competitionId>, <matchId>);
```

Returns an array with information about the match.

### Store the result of a match
```php
$matchData = array(
    'score' => 4,
    'scoreOpponent' => 1,
);

$pwned->updateMatch('tournament', <competitionId>, <matchId>, $matchData);
```

Stores the result of a played match. score and scoreOpponent refers to the score of the teams identified by "signup" and "signupOpponent" in the match description returned from ```getRound()```, ```getRounds()``` or ```getMatch()```.

#### Optional keys for a match result
* 'score' => int: The score of the home team (identified by signup in the match element)
* 'scoreOpponent' => int: The score of the away team (identified by signupOpponent in the match element)
* 'walkover' => string: can be either 'signup' or 'signupOpponent' and will register a walkover win to either signup

### Pinging the API end point
```php
$pwned->ping();
```

Tests if the authentication information is correct (the private and public key) and that the endpoint is alive and well. Returns 'pong' if successful.