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
            'tournamentType' => 'singleelim',
            'teamCount' => 16,
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
        $start = 'A';

        for ($i = 0; $i < $count; $i++)
        {
            $signups[] = array(
                'name' => $start++,
                'hasServer' => true,
                'isAccepted' => true,
                'onWaitingList' => false,
                'contact' => uniqid('Contact ', true),
                'remoteId' => rand(1, 100000000),
            );
        }

        return $signups;
    }

    /**
     * Print out a tournament setup from brackets information
     *
     * @param $brackets
     */
    protected function printBrackets($brackets)
    {
        foreach ($brackets as $bracket)
        {
            print($bracket['name'] . "\n");
            print(str_repeat("-", strlen($bracket['name'])) . "\n\n");

            if (!empty($bracket['rounds'][0]['groups']))
            {
                foreach ($bracket['rounds'][0]['groups'] as $group)
                {
                    print("    Group: " . $group['index'] . "\n");
                    print("--------------\n\n");

                    foreach ($group['standing'] as $entry)
                    {
                        print($entry['rank'] . '. ' . $entry['signup']['name'] . "\n");
                    }

                    print("\n");
                }
            }

            $output = array();

            for ($i = 0; $i < count($bracket['rounds'][0]['matches']); $i++)
            {
                $output[] = '';
                $output[] = '';
                $output[] = '';
                $output[] = '';
            }

            $roundIndex = 0;

            foreach ($bracket['rounds'] as $round)
            {
                $roundWidth = array_reduce($round['matches'], function ($roundWidth, $match) {
                    if (strlen($match['signup']['name']) > $roundWidth)
                    {
                        $roundWidth = strlen($match['signup']['name']);
                    }

                    if (strlen($match['signupOpponent']['name']) > $roundWidth)
                    {
                        $roundWidth = strlen($match['signupOpponent']['name']);
                    }

                    return $roundWidth;
                }, 0);

                $matchCount = count($round['matches']);

                $nameGetter = function ($match, $key = 'signup') {
                    if (!empty($match[$key]['name']))
                    {
                        return $match[$key]['name'];
                    }

                    return '---';
                };

                $scoreGetter = function ($match, $key = 'score') {
                    if ($match['isWalkover'])
                    {
                        if ($match[$key])
                        {
                            return 'WO';
                        }
                        else
                        {
                            return '';
                        }
                    }

                    $opponent = $key != 'score' ? 'score' : 'scoreOpponent';

                    return $match[$key] . ($match[$key] > $match[$opponent] ? ' (*)' : '    ');
                };

                $i = 0;

                foreach ($round['matches'] as $match)
                {
                    $nameLen = strlen($nameGetter($match, 'signup'));
                    $opponentLen = strlen($nameGetter($match, 'signupOpponent'));

                    $output[$i] .= $nameGetter($match, 'signup') . str_pad($scoreGetter($match, 'score'), $roundWidth - $nameLen + 9, ' ', STR_PAD_LEFT) . '   ';
                    $output[$i+1] .= $nameGetter($match, 'signupOpponent') . str_pad($scoreGetter($match, 'scoreOpponent'), $roundWidth - $opponentLen + 9, ' ', STR_PAD_LEFT) . '   ';;

                    $output[$i+2] .= str_repeat(' ', $roundWidth + 13);
                    $output[$i+3] .= str_repeat(' ', $roundWidth + 13);

                    $i += 4;
                }
            }

            print(join("\n", $output));

            print("\n\n\n");
        }
    }

    public function printRounds($rounds)
    {
        foreach ($rounds as $round)
        {
            $header = 'Round: ' . $round['roundNumber'] . ' (' . $round['id'] . ')';
            print($header . "\n" . str_repeat('-', strlen($header)) . "\n\n");

            foreach ($round['matches'] as $match)
            {
                $name = $match['signup'] ? $match['signup']['name'] : '(unknown)';
                $nameOpponent = $match['signupOpponent'] ? $match['signupOpponent']['name'] : '(unknown)';

                print($name . ' - ' . $nameOpponent . "   " . ($match['isWalkover'] ? 'WO' : '') . "\n");
            }

            print("\n\n");
        }
    }
}