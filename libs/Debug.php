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

        if (php_sapi_name() != 'cli') {
            $prepare = function ($str, $indent = true) {
                return '<pre>' . $this->format($str, $indent) . '</pre>';
            };
        } else {
            $prepare = function ($str, $indent = true) {
                return $this->format($str, $indent);
            };
        }

        $key = $file . ':' . $line;

        if ($last_key != $key) {
            fputs($this->output, $prepare(sprintf("\n** DEBUG: %s(%d)**\n", $file, $line), false));
            $last_key = $key;
        }

        if (extension_loaded('xdebug')) {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                var_dump($data[$i]);
            }
        } else {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                ob_start($prepare);
                var_dump($data[$i]);
                ob_end_flush();
            }
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

        if (php_sapi_name() != 'cli') {
            $prepare = function ($str, $indent = true) {
                return '<pre>' . $this->format($str, $indent) . '</pre>';
            };
        } else {
            $prepare = function ($str, $indent = true) {
                return $this->format($str, $indent);
            };
        }

        $key = $file . ':' . $line;

        if ($last_key != $key) {
            fputs($this->output, $prepare(sprintf("\n** DEBUG: %s(%d)**\n", $file, $line), false));
            $last_key = $key;
        }

        ob_start($prepare);
        vprintf($msg, $data);
        ob_end_flush();
    }
}
