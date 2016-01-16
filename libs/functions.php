<?php

/*
 * This file is part of the 'octris/debug' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Global debug helper functions.
 *
 * @copyright   Copyright (c) 2012-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */

/**
 * Dump contents of one or multiple variables.
 *
 * @param   mixed         ...$params        Parameters to pass to \Octris\Core\Debug::ddump.
 */
function ddump(...$params)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];

    \Octris\Debug::getInstance()->ddump($trace['file'], $trace['line'], ...$params);
}

/**
 * Print formatted debug message. Message formatting follows the rules of sprints/vsprintf.
 *
 * @param   string      $msg                Message with optional placeholders to print.
 * @param   mixed       ...$params          Parameters to pass to \Octris\Core\Debug::dprint.
 */
function dprint($msg, ...$params)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];

    \Octris\Debug::getInstance()->dprint($trace['file'], $trace['line'], $msg, ...$params);
}
