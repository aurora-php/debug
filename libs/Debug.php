<?php

/*
 * This file is part of the 'octris/debug' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris;

/**
 * Debug class.
 *
 * @copyright   Copyright (c) 2012-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Debug
{
    /**
     * Instance of debug class.
     *
     * @type    \Octris\Debug|null
     */
    protected static $instance = null;

    /**
     * Output resource handle, defaults to stdout.
     *
     * @type    resource
     */
    protected $output;

    /**
     * Number of spaces to indent.
     *
     * @type    int
     */
    protected $indent = 3;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->output = fopen('php://output', 'w');
    }

    /**
     * Get instance of debug class.
     *
     * @return  \Octris\Core\Debug
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Set output resource handle.
     *
     * @param   resource    $handle             Handle to output messages to.
     */
    public function setOutput($handle)
    {
        if (!is_resource($handle)) {
            throw new \InvalidArgumentException('Handle must be of type "resource".');
        }

        $this->output = $handle;
    }

    /**
     * Checks whether output is probably on a web site.
     *
     * @return  bool                            Returns true if check succeeded.
     */
    protected function isHtml()
    {
        return (php_sapi_name() != 'cli' && stream_get_meta_data($this->output)['uri'] == 'php://output');
    }

    /**
     * Format output.
     *
     * @param   string      $str                String to output.
     * @param   bool        $indent             Whether to indent output.
     * @return  string                          Formatted output.
     */
    protected function format($str, $indent = true)
    {
        $spaces = str_repeat(' ', ((int)$indent) * $this->indent);

        return $spaces . trim(
            str_replace(
                "\n",
                "\n" . $spaces,
                (php_sapi_name() != 'cli' ? htmlspecialchars($str) : $str)
            )
        ) . "\n";
    }

    /**
     * Dump contents of one or multiple variables. This method should not be called directly, use global
     * function 'ddump' instead.
     *
     * @param   string      $file               File the ddump command was called from.
     * @param   int         $line               Line number of file the ddump command was called from.
     * @param   ...         $data               Data to dump.
     */
    public function ddump($file, $line, ...$data)
    {
        static $last_key = '';

        if (($is_html = $this->isHtml())) {
            fputs($this->output, '<pre>');
        }

        $key = $file . ':' . $line;

        if ($last_key != $key) {
            fputs($this->output, $this->format(sprintf("\n** DEBUG: %s(%d)**\n", $file, $line), false));
            $last_key = $key;
        }

        if (extension_loaded('xdebug')) {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                var_dump($data[$i]);
            }
        } else {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                ob_start(array($this, 'format'));
                var_dump($data[$i]);
                ob_end_flush();
            }
        }

        if ($is_html) {
            fputs($this->output, '</pre>');
        }
    }

    /**
     * Print formatted debug message. Message formatting follows the rules of sprints/vsprintf.
     * This method should not be called directly, use global function 'dprint' instead.
     *
     * @param   string      $file               File the ddump command was called from.
     * @param   int         $line               Line number of file the ddump command was called from.
     * @param   string      $msg                Message with optional placeholders to print.
     * @param   mixed       ...$data            Additional optional parameters to print.
     */
    public function dprint($file, $line, $msg, ...$data)
    {
        static $last_key = '';

        if (($is_html = $this->isHtml())) {
            fputs($this->output, '<pre>');
        }

        $key = $file . ':' . $line;

        if ($last_key != $key) {
            fputs($this->output, $this->format(sprintf("\n** DEBUG: %s(%d)**\n", $file, $line), false));
            $last_key = $key;
        }

        ob_start(array($this, 'format'));
        vprintf($msg, $data);
        ob_end_flush();

        if ($is_html) {
            fputs($this->output, '</pre>');
        }
    }

    /**
     * Output error message with stack trace.
     *
     * @param   string              $context                Context the error occured in.
     * @param   int                 $context_line           Line of the context.
     * @param   array               $info                   Key/Value pairs of information to print.
     * @param   string|null         $trace                  Optional stack trace.
     * @param   \Exception|null     $exception              Optional exception to throw after output.
     */
    public function error($context, $context_line, array $info, $trace = null, \Exception $exception = null)
    {
        // start formatting
        if (($is_html = $this->isHtml())) {
            // Yes, this is an injection but it's OK here, because we want to try to force visible error output
            // even when in a context the output would normally not visible in. {{
            fputs($this->output, '--></script>">\'>');
            // }}

            fputs($this->output, '<pre>');
        }

        // general information
        fputs($this->output, $this->format(sprintf("\n** ERROR: %s(%d)**\n", $context, $context_line), false));

        $max = array_reduce(array_keys($info), function($carry, $key) {
            return max($carry, strlen($key) + 3);
        }, 0);

        foreach ($info as $key => $value) {
            fputs($this->output, $this->format(sprintf('%-' . $max . "s %s\n", $key . ':  ', $value)));
        }

        // output stacktrace
        if (is_null($trace)) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = ob_get_contents();
            ob_end_clean();
        }

        fputs($this->output, "\n" . $this->format($trace) . "\n");

        if (!is_null($exception)) {
            // exception
            throw $exception;
        } elseif ($is_html) {
            fputs($this->output, '</pre>');
        }
    }
}
