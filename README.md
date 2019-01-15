# ActivityPub-PHP
> A library to turn any PHP project into a full [ActivityPub](https://activitypub.rocks) implementation

**This library is a work-in-progress. The documentation below reflects what the API will look like once it's done.**

ActivityPub-PHP is a library that embeds a full ActivityPub server into any PHP project. It works with any SQL database and any web framework. At a high level, it provides a request handler that you can route ActivityPub requests to which will take care of persisting the received activity, performing any necessary side effects, and delivering the activity to other federated servers.

## What it does
- stores incoming activities to your project's existing SQL database in a configurable fashion
- implement both the client-to-server and the server-to-server parts of the ActivityPub protocol
- verify HTTP signatures on incoming ActivityPub requests and sign outgoing ActivityPub requests
- provide a PHP API so you can create and manage Actors and send activities directly from code
- hook into your application's user authentication logic to provide ways to associate your users with ActivityPub actors
- manage the JSON-LD context for you, with hooks if you need to add custom fields
- support PHP > 5.*

## What it doesn't do
- handle standalone user authentication - this is up to your particular application
- support non-SQL databases
- provide a UI

## Installation
ActivityPub-PHP is available via Composer:

    $ composer require pterotype/activitypub-php

## Usage
Basic usage example:

``` php
<?php
use ActivityPub\ActivityPub;

// Constructing the ActivityPub instance
$activitypub = new ActivityPub( array(
    // Function to determine if the current request should be associated
    // with an ActivityPub actor. It should return the actor id associated
    // with the current request, or false if the current request is not associated
    // with the actor. This is where you can plug in your application's user
    // management system:
    'authFunction' => function() {
        if ( current_user_is_logged_in() ) {
            return get_actor_id_for_current_user();
        } else {
            return false;
        }
    },
    // Database connection options, passed directly to Doctrine:
    'dbOptions' => array(
        'driver' => 'pdo_mysql',
        'user' => 'mysql'
        'password' => 'thePa$$word',
        'dbname' => 'my-database',
    ),
    // Database table name prefix for compatibility with $wpdb->prefix, etc.:
    // Default: ''
    'dbPrefix' => 'activitypub_',
) );

// Routing incoming ActivityPub requests to the ActivityPub-PHP
if ( in_array( $_SERVER['HTTP_ACCEPT'],
               array( 'application/ld+json', 'application/activity+json' ) ) ) {
    // Handle the request, perform any side effects and delivery,
    // and return a Symfony Response
    $response = $activitypub->handle();
    // Send the response back to the client
    $response->send();
}

// Creating a new actor
function createActor()
{
    $actorArray = array(
        'id' => 'https://mysite.com/my_actor',
        'type' => 'Person',
        'preferredUsername' => 'myActor',
    );
    $actor = $activitypub->createActor( $actorArray );
    // $actor has all the ActivityPub actor fields, e.g. inbox, outbox, followers, etc.
}

// Posting activities from code
function postActivity()
{
    $actor = $activitypub->getActor( 'https://mysite.com/my_actor' );
    $note = array(
        'type' => 'Note',
        'content' => 'This is a great note',
        'to' => $actor['followers'],
    );
    $actor->create( $note );
    // also $actor->update(), $actor->delete(), etc.
}
?>

```
