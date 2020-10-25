<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: sqlite.php,v 1.1 2004/12/25 20:04:34 garbelini Exp $

/**
 * The Log_sqlite class is a concrete implementation of the Log::
 * abstract class which sends messages to an Sqlite database.
 * Each entry occupies a separate row in the database.
 *
 * This implementation uses PHP native Sqlite functions.
 *
 * CREATE TABLE log_table (
 *  id          INTEGER PRIMARY KEY NOT NULL,
 *  logtime     NOT NULL,
 *  ident       CHAR(16) NOT NULL,
 *  priority    INT NOT NULL,
 *  message
 * );
 *
 * @author  Bertrand Mansion <bmansion@mamasam.com>
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.8.3
 *
 * @example sqlite.php      Using the Sqlite handler.
 */
class Log_sqlite extends Log
{
    /**
     * Array containing the connection defaults
     * @var array
     */

    public $_options = ['mode' => 0666,
                          'persistent' => false, ];

    /**
     * Object holding the database handle.
     * @var object
     */

    public $_db = null;

    /**
     * Flag indicating that we're using an existing database connection.
     * @var bool
     */

    public $_existingConnection = false;

    /**
     * String holding the database table to use.
     * @var string
     */

    public $_table = 'log_table';

    /**
     * Constructs a new sql logging object.
     *
     * @param string $name         The target SQL table.
     * @param string $ident        The identification field.
     * @param mixed  $conf         Can be an array of configuration options used
     *                             to open a new database connection
     *                             or an already opened sqlite connection.
     * @param int    $level        Log messages up to and including this level.
     */

    public function __construct($name, $ident, &$conf, $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());

        $this->_table = $name;

        $this->_ident = $ident;

        $this->_mask = Log::UPTO($level);

        if (is_array($conf)) {
            foreach ($conf as $k => $opt) {
                $this->_options[$k] = $opt;
            }
        } else {
            // If an existing database connection was provided, use it.

            $this->_db = &$conf;

            $this->_existingConnection = true;
        }
    }

    /**
     * Opens a connection to the database, if it has not already
     * been opened. This is implicitly called by log(), if necessary.
     *
     * @return bool   True on success, false on failure.
     */

    public function open()
    {
        if (is_resource($this->_db)) {
            $this->_opened = true;

            return $this->_createTable();
        }  

        /* Set the connection function based on the 'persistent' option. */

        if (empty($this->_options['persistent'])) {
            $connectFunction = 'sqlite_open';
        } else {
            $connectFunction = 'sqlite_popen';
        }

        /* Attempt to connect to the database. */

        if ($this->_db = $connectFunction(
                $this->_options['filename'],
                (int)$this->_options['mode'],
                $error
            )) {
            $this->_opened = true;

            return $this->_createTable();
        }

        return $this->_opened;
    }

    /**
     * Closes the connection to the database if it is still open and we were
     * the ones that opened it.  It is the caller's responsible to close an
     * existing connection that was passed to us via $conf['db'].
     *
     * @return bool   True on success, false on failure.
     */

    public function close()
    {
        /* We never close existing connections. */

        if ($this->_existingConnection) {
            return false;
        }

        if ($this->_opened) {
            $this->_opened = false;

            sqlite_close($this->_db);
        }

        return (false === $this->_opened);
    }

    /**
     * Inserts $message to the currently open database.  Calls open(),
     * if necessary.  Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param mixed $message  String or object containing the message to log.
     * @param null  $priority The priority of the message.  Valid
     *                        values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                        PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                        PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     * @return bool  True on success or false on failure.
     */

    public function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */

        if (null === $priority) {
            $priority = $this->_priority;
        }

        /* Abort early if the priority is above the maximum logging level. */

        if (!$this->_isMasked($priority)) {
            return false;
        }

        /* If the connection isn't open and can't be opened, return failure. */

        if (!$this->_opened && !$this->open()) {
            return false;
        }

        // Extract the string representation of the message.

        $message = $this->_extractMessage($message);

        // Build the SQL query for this log entry insertion.

        $q = sprintf(
            'INSERT INTO [%s] (logtime, ident, priority, message) ' .
                     "VALUES ('%s', '%s', %d, '%s')",
            $this->_table,
            strftime('%Y-%m-%d %H:%M:%S', time()),
            sqlite_escape_string($this->_ident),
            $priority,
            sqlite_escape_string($message)
        );

        if (!($res = @sqlite_unbuffered_query($this->_db, $q))) {
            return false;
        }

        $this->_announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

    /**
     * Checks whether the log table exists and creates it if necessary.
     *
     * @return bool  True on success or false on failure.
     */

    public function _createTable()
    {
        $q = "SELECT name FROM sqlite_master WHERE name='" . $this->_table .
             "' AND type='table'";

        $res = sqlite_query($this->_db, $q);

        if (0 == sqlite_num_rows($res)) {
            $q = 'CREATE TABLE [' . $this->_table . '] (' .
                 'id INTEGER PRIMARY KEY NOT NULL, ' .
                 'logtime NOT NULL, ' .
                 'ident CHAR(16) NOT NULL, ' .
                 'priority INT NOT NULL, ' .
                 'message)';

            if (!($res = sqlite_unbuffered_query($this->_db, $q))) {
                return false;
            }
        }

        return true;
    }
}
