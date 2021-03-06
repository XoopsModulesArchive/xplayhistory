<?php
/**
 * $Header: /cvsroot/xoops/modules/xplayhistory/classes/Log/file.php,v 1.1 2004/12/25 20:04:34 garbelini Exp $
 *
 * @version $Revision: 1.1 $
 */

/**
 * The Log_file class is a concrete implementation of the Log abstract
 * class that logs messages to a text file.
 *
 * @author  Jon Parise <jon@php.net>
 * @author  Roman Neuhauser <neuhauser@bellavista.cz>
 * @since   Log 1.0
 *
 * @example file.php    Using the file handler.
 */
class Log_file extends Log
{
    /**
     * String containing the name of the log file.
     * @var string
     */

    public $_filename = 'php.log';

    /**
     * Handle to the log file.
     * @var resource
     */

    public $_fp = false;

    /**
     * Should new log entries be append to an existing log file, or should the
     * a new log file overwrite an existing one?
     * @var bool
     */

    public $_append = true;

    /**
     * Integer (in octal) containing the log file's permissions mode.
     * @var int
     */

    public $_mode = 0644;

    /**
     * String containing the format of a log line.
     * @var string
     */

    public $_lineFormat = '%1$s %2$s [%3$s] %4$s';

    /**
     * String containing the timestamp format.  It will be passed directly to
     * strftime().  Note that the timestamp string will generated using the
     * current locale.
     * @var string
     */

    public $_timeFormat = '%b %d %H:%M:%S';

    /**
     * Hash that maps canonical format keys to position arguments for the
     * "line format" string.
     * @var array
     */

    public $_formatMap = ['%{timestamp}' => '%1$s',
                            '%{ident}' => '%2$s',
                            '%{priority}' => '%3$s',
                            '%{message}' => '%4$s',
                            '%\{' => '%%{', ];

    /**
     * String containing the end-on-line character sequence.
     * @var string
     */

    public $_eol = "\n";

    /**
     * Constructs a new Log_file object.
     *
     * @param string $name     Ignored.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     */

    public function __construct(
        $name,
        $ident = '',
        $conf = [],
        $level = PEAR_LOG_DEBUG
    ) {
        $this->_id = md5(microtime());

        $this->_filename = $name;

        $this->_ident = $ident;

        $this->_mask = Log::UPTO($level);

        if (isset($conf['append'])) {
            $this->_append = $conf['append'];
        }

        if (!empty($conf['mode'])) {
            $this->_mode = $conf['mode'];
        }

        if (!empty($conf['lineFormat'])) {
            $this->_lineFormat = str_replace(
                array_keys($this->_formatMap),
                array_values($this->_formatMap),
                $conf['lineFormat']
            );
        }

        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }

        if (!empty($conf['eol'])) {
            $this->_eol = $conf['eol'];
        } else {
            $this->_eol = (mb_strstr(PHP_OS, 'WIN')) ? "\r\n" : "\n";
        }

        register_shutdown_function([&$this, '_Log_file']);
    }

    /**
     * Destructor
     */

    public function _Log_file()
    {
        if ($this->_opened) {
            $this->close();
        }
    }

    /**
     * Creates the given directory path.  If the parent directories don't
     * already exist, they will be created, too.
     *
     * @param   string  $path       The full directory path to create.
     * @param   int $mode       The permissions mode with which the
     *                              directories will be created.
     *
     * @return  true if the full path is successfully created or already
     *          exists.
     */

    public function _mkpath($path, $mode = 0700)
    {
        static $depth = 0;

        /* Guard against potentially infinite recursion. */

        if ($depth++ > 25) {
            trigger_error(
                '_mkpath(): Maximum recursion depth (25) exceeded',
                E_USER_WARNING
            );

            return false;
        }

        /* We're only interested in the directory component of the path. */

        $path = dirname($path);

        /* If the directory already exists, return success immediately. */

        if (is_dir($path)) {
            $depth = 0;

            return true;
        }

        /*
         * In order to understand recursion, you must first understand
         * recursion ...
         */

        if (false === $this->_mkpath($path, $mode)) {
            return false;
        }

        return @mkdir($path, $mode);
    }

    /**
     * Opens the log file for output.  If the specified log file does not
     * already exist, it will be created.  By default, new log entries are
     * appended to the end of the log file.
     *
     * This is implicitly called by log(), if necessary.
     */

    public function open()
    {
        if (!$this->_opened) {
            /* If the log file's directory doesn't exist, create it. */

            if (!is_dir(dirname($this->_filename))) {
                $this->_mkpath($this->_filename);
            }

            /* Obtain a handle to the log file. */

            $this->_fp = fopen($this->_filename, ($this->_append) ? 'a' : 'w');

            $this->_opened = (false !== $this->_fp);

            /* Attempt to set the log file's mode. */

            @chmod($this->_filename, $this->_mode);
        }

        return $this->_opened;
    }

    /**
     * Closes the log file if it is open.
     */

    public function close()
    {
        /* If the log file is open, close it. */

        if ($this->_opened && fclose($this->_fp)) {
            $this->_opened = false;
        }

        return (false === $this->_opened);
    }

    /**
     * Flushes all pending data to the file handle.
     *
     * @since Log 1.8.2
     */

    public function flush()
    {
        return fflush($this->_fp);
    }

    /**
     * Logs $message to the output window.  The message is also passed along
     * to any Log_observer instances that are observing this Log.
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

        /* If the log file isn't already open, open it now. */

        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */

        $message = $this->_extractMessage($message);

        /* Build the string containing the complete log line. */

        $line = sprintf(
            $this->_lineFormat,
            strftime($this->_timeFormat),
            $this->_ident,
            $this->priorityToString($priority),
            $message
        ) . $this->_eol;

        /* Write the log line to the log file. */

        $success = (false !== fwrite($this->_fp, $line));

        /* Notify observers about this log message. */

        $this->_announce(['priority' => $priority, 'message' => $message]);

        return $success;
    }
}
