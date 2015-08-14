<?php

/*
    Logging example. Writes specially formatted JSON to a file and plain JSON to
    a Redis server. 
    
    Another application reads these messages on Redis and sends them to a remote
    logging application using the Monolog Raven handler.

    @author     Luke Skibinski <l.skibinski@elifesciences.org>
    @copyright  eLife Sciences, 2015
    @license    GNU GPLv3, http://www.gnu.org/licenses/gpl-3.0.en.html
*/

use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RedisHandler;

# reset error handling for this script
error_reporting(-1);
ini_set('display_errors', 1);

require_once('vendor/autoload.php');

function el_format_record(array $record) {
    return array(
        "timestamp" => date_timestamp_get($record['datetime']),
        "log_level" => $record['level_name'],
        "process" => $record['channel'],
        "section" => __FILE__, # pretty sure this won't work as I expect it to
        "message" => $record['message'],
        "context" => $record['context'],
    );
}

class eLifeJsonFormatter extends Monolog\Formatter\JsonFormatter {
    public function format(array $record) {
        return parent::format(el_format_record($record));
    }

    public function formatBatch(array $records) {
        $records = array_map('el_format_record', $records);
        return parent::formatBatch($records);
    }
}

$log = new Logger('example-app');

# send a wrangled JSON record to a file
$formatter = new eLifeJsonFormatter();
$stream = new StreamHandler('/tmp/monolog-test.log', Logger::DEBUG);
$stream->setFormatter($formatter);
$log->pushHandler($stream);

# send regular JSON record to Redis
$client = new Predis\Client();
$queue_name = "log_messages";
$redis = new RedisHandler($client, $queue_name);
$redis->setFormatter(new Monolog\Formatter\JsonFormatter());
$log->pushHandler($redis);

#
# log stuff
#

$log->addDebug('this is a debug');
$log->addWarning('this is a warning');
$log->addError('this is an error');
