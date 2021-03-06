<?php
/**
 * $Header: /cvsroot/xoops/modules/xplayhistory/classes/Log/mcal.php,v 1.1 2004/12/25 20:04:34 garbelini Exp $
 * $Horde: horde/lib/Log/mcal.php,v 1.2 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision: 1.1 $
 */

/**
 * The Log_mcal class is a concrete implementation of the Log::
 * abstract class which sends messages to a local or remote calendar
 * store accessed through MCAL.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since Horde 1.3
 * @since Log 1.0
 */
class Log_mcal extends Log
{
    /**
     * holding the calendar specification to connect to.
     * @var string
     */

    public $_calendar = '{localhost/mstore}';

    /**
     * holding the username to use.
     * @var string
     */

    public $_username = '';

    /**
     * holding the password to use.
     * @var string
     */

    public $_password = '';

    /**
     * holding the options to pass to the calendar stream.
     * @var int
     */

    public $_options = 0;

    /**
     * ResourceID of the MCAL stream.
     * @var string
     */

    public $_stream = '';

    /**
     * Integer holding the log facility to use.
     * @var string
     */

    public $_name = LOG_SYSLOG;

    /**
     * Constructs a new Log_mcal object.
     *
     * @param string $name     The category to use for our events.
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

        $this->_name = $name;

        $this->_ident = $ident;

        $this->_mask = Log::UPTO($level);

        $this->_calendar = $conf['calendar'];

        $this->_username = $conf['username'];

        $this->_password = $conf['password'];

        $this->_options = $conf['options'];
    }

    /**
     * Opens a calendar stream, if it has not already been
     * opened. This is implicitly called by log(), if necessary.
     */

    public function open()
    {
        if (!$this->_opened) {
            $this->_stream = mcal_open(
                $this->_calendar,
                $this->_username,
                $this->_password,
                $this->_options
            );

            $this->_opened = true;
        }

        return $this->_opened;
    }

    /**
     * Closes the calendar stream, if it is open.
     */

    public function close()
    {
        if ($this->_opened) {
            mcal_close($this->_stream);

            $this->_opened = false;
        }

        return (false === $this->_opened);
    }

    /**
     * Logs $message and associated information to the currently open
     * calendar stream. Calls open() if necessary. Also passes the
     * message along to any Log_observer instances that are observing
     * this Log.
     *
     * @param mixed $message  String or object containing the message to log.
     * @param null  $priority The priority of the message. Valid
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

        /* Extract the string representation of the message. */

        $message = $this->_extractMessage($message);

        $date_str = date('Y:n:j:G:i:s');

        $dates = explode(':', $date_str);

        mcal_event_init($this->_stream);

        mcal_event_set_title($this->_stream, $this->_ident);

        mcal_event_set_category($this->_stream, $this->_name);

        mcal_event_set_description($this->_stream, $message);

        mcal_event_add_attribute($this->_stream, 'priority', $priority);

        mcal_event_set_start(
            $this->_stream,
            $dates[0],
            $dates[1],
            $dates[2],
            $dates[3],
            $dates[4],
            $dates[5]
        );

        mcal_event_set_end(
            $this->_stream,
            $dates[0],
            $dates[1],
            $dates[2],
            $dates[3],
            $dates[4],
            $dates[5]
        );

        mcal_append_event($this->_stream);

        $this->_announce(['priority' => $priority, 'message' => $message]);

        return true;
    }
}
