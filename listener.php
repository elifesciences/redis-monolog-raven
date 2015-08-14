<?php

/*
    Reads json encoded values *from* Redis, decodes and writes values *to* a
    Raven destination specified with `RAVEN_DSN` using Monolog.

    @author     Luke Skibinski <l.skibinski@elifesciences.org>
    @copyright  eLife Sciences, 2015
    @license    GNU GPLv3, http://www.gnu.org/licenses/gpl-3.0.en.html
*/

use Monolog\Logger;
use Monolog\Handler\RavenHandler;

# reset error handling for this script
error_reporting(-1);
ini_set('display_errors', 1);

require('vendor/autoload.php');

#
# settings
#

# where does Raven send this stuff we're giving it?
# looks like: "https://public:private@popular.loganalyser.com/what/ever/"
define("RAVEN_DSN", null);
# Redis List key we monitor for changes, same one we log to
define("QUEUE_NAME", "log_messages");
define("TIMEOUT", 10); // seconds

$log = new Logger('log-proxy');


#
# assertion handling. 
# kills script if assertion fails.
#

function cb_assertion($script, $line, $message) {
    $message = ereg_replace('^.*//\*', '', $message);
    echo "design error in script ${script}:${line}\"${message}\"\n";
    exit(1);
}
assert_options(ASSERT_CALLBACK, 'cb_assertion');

#
# add a logging processor. 
# the message we're pulling back from Redis is the original json encoded record.
# this function replaces the one given.
#

function el_json_decode($record) {
    $as_assoc_array = true;
    return json_decode($record["message"], $as_assoc_array);
}
$log->pushProcessor('el_json_decode');


#
# raven/sentry
# remote log analysers are fine things, but the overhead in establishing a 
# connection cannot be tolerated.
#

# TODO: does the RavenHandler re-establish a connection if it is dropped?

require('vendor/raven/raven/lib/Raven/Autoloader.php');
Raven_Autoloader::register();


$client = new Raven_Client(RAVEN_DSN, array(
    // pass along the version of your application
    'release' => '2015-08-13',
));
$log->pushHandler(new RavenHandler($client));

#
# redis
#


# doesn't actually connect to Redis ...
$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
]);

# delete whatever happens to be in that value now
// $redis->del(QUEUE_NAME); # good for testing, don't want to do this in production.

# push an initial value into the list to check everything is working
define("CONTROL_SIGNAL", 0xDEADBEEF);
$redis->lPush(QUEUE_NAME, CONTROL_SIGNAL);

# this is amongst the grossest things I've ever seen.
assert('$redis->exists(QUEUE_NAME); //* could not find queue ' . QUEUE_NAME);

while (true) {
    list($_, $record) = $redis->brPop(QUEUE_NAME, TIMEOUT);
    if(empty($record) || $record == CONTROL_SIGNAL) {
        //echo "null/control signal. continuing\n";
        continue;
    }
    echo "received " . $record;
    # the 'DEBUG' here doesn't matter, our record processor overwrites this
    $log->addDebug($record);
}

echo "done\n";
