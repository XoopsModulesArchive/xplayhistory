<?php
/**
 * $Header: /cvsroot/xoops/modules/xplayhistory/classes/Log/composite.php,v 1.1 2004/12/25 20:04:34 garbelini Exp $
 * $Horde: horde/lib/Log/composite.php,v 1.2 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision: 1.1 $
 */

/**
 * The Log_composite:: class implements a Composite pattern which
 * allows multiple Log implementations to receive the same events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@php.net>
 *
 * @since Horde 1.3
 * @since Log 1.0
 *
 * @example composite.php   Using the composite handler.
 */
class Log_composite extends Log
{
    /**
     * Array holding all of the Log instances to which log events should be
     * sent.
     *
     * @var array
     */

    public $_children = [];

    /**
     * Constructs a new composite Log object.
     *
     * @param bool $name  This parameter is ignored.
     * @param bool $ident This parameter is ignored.
     * @param bool $conf  This parameter is ignored.
     * @param int  $level This parameter is ignored.
     */

    public function __construct(
        $name = false,
        $ident = false,
        $conf = false,
        $level = PEAR_LOG_DEBUG
    ) {
    }

    /**
     * Opens the child connections.
     */

    public function open()
    {
        if (!$this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->open();
            }

            $this->_opened = true;
        }
    }

    /**
     * Closes any child instances.
     */

    public function close()
    {
        if ($this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->close();
            }

            $this->_opened = false;
        }
    }

    /**
     * Flushes all open child instances.
     *
     * @since Log 1.8.2
     */

    public function flush()
    {
        if ($this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->flush();
            }
        }
    }

    /**
     * Sends $message and $priority to each child of this composite.
     *
     * @param mixed $message        String or object containing the message
     *                              to log.
     * @param null  $priority       (optional) The priority of the message.
     *                              Valid values are: PEAR_LOG_EMERG,
     *                              PEAR_LOG_ALERT, PEAR_LOG_CRIT,
     *                              PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                              PEAR_LOG_NOTICE, PEAR_LOG_INFO, and
     *                              PEAR_LOG_DEBUG.
     *
     * @return bool  True if the entry is successfully logged.
     */

    public function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */

        if (null === $priority) {
            $priority = $this->_priority;
        }

        foreach ($this->_children as $id => $child) {
            $this->_children[$id]->log($message, $priority);
        }

        $this->_announce(['priority' => $priority, 'message' => $message]);

        return true;
    }

    /**
     * Returns true if this is a composite.
     *
     * @return bool  True if this is a composite class.
     */

    public function isComposite()
    {
        return true;
    }

    /**
     * Sets this identification string for all of this composite's children.
     *
     * @param string    $ident      The new identification string.
     *
     * @since  Log 1.6.7
     */

    public function setIdent($ident)
    {
        foreach ($this->_children as $id => $child) {
            $this->_children[$id]->setIdent($ident);
        }
    }

    /**
     * Adds a Log instance to the list of children.
     *
     * @param object    $child      The Log instance to add.
     *
     * @return bool  True if the Log instance was successfully added.
     */

    public function addChild(&$child)
    {
        /* Make sure this is a Log instance. */

        if (!is_a($child, 'Log')) {
            return false;
        }

        $this->_children[$child->_id] = &$child;

        return true;
    }

    /**
     * Removes a Log instance from the list of children.
     *
     * @param object    $child      The Log instance to remove.
     *
     * @return bool  True if the Log instance was successfully removed.
     */

    public function removeChild($child)
    {
        if (!is_a($child, 'Log') || !isset($this->_children[$child->_id])) {
            return false;
        }

        unset($this->_children[$child->_id]);

        return true;
    }
}
