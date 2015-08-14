# redis-monolog-raven

Using Monolog's Raven handler introduces an unacceptable overhead of ~1.5 
seconds to the execution of a php script.

This is an example of logging to Redis and then another script doing a perpetual
blocking read from Redis, sending what it receives to Raven.

## installation

Assumes PHP and Composer are installed.

```bash
# apt-get install redis-server # Ubuntu
$ git clone https://github.com/elifesciences/redis-monolog-raven
$ cd redis-monolog-raven
$ composer install
```

## usage
    
The Redis listener script will expect the DSN to be in an environment variable 
called `RAVEN_DSN`. How it makes it into the environment is up to you.

The script that listen to Redis for input can be called like this:

```bash
$ php listener.php RAVEN_DSN=https://public:private@app.getsentry.com/12345
```
    
The example script that does the logging can be called like:

```bash
$ php logging.php
```

## Copyright & Licence

Copyright 2015 eLife Sciences. Licensed under the [GPLv3](LICENCE.txt)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
