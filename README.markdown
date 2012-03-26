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

### Create a Pwned client instance
```php
$pwned = new Pwned_Client('<url>', '<publicKey>', '<privateKey>');
```

### To retrieve information about a tournament
```php
$info = $pwned->getCompetition('tournament', <tournamentId>);
```

This will return an associative array containing information about the 
tournament represented by the tournamentId (an integer).