<?php
/**
 * Courtesy of https://codeinphp.github.io/post/exceptions-are-bad-yet-awesome/
 * taken without express permission.
 *
 * A class that handles both errors and exceptions and generates an Exception for both.
 *
 * In case of "production" environment, errors are logged to file
 * In case of "development" environment, errors are echoed out on screen
 *
 * Usage:
 *
 * new ExceptionHandler('development', '/myDir/logs');
 *
 * Note: Make sure to use it on very beginning of your project or bootstrap file.
 *
 */
class ExceptionHandler {
    // file path where all exceptions will be written to
    protected $log_file = '';
    // environment type
    protected $environment = '';

    public function __construct($environment = 'production', $log_file = 'logs')
    {
        $this->environment = $environment;
        $this->log_file = $log_file;

        // NOTE: it is better to set ini_set settings via php.ini file instead to deal with parse errors.
        if ($this->environment === 'production') {
            // disable error reporting
            error_reporting(0);
            ini_set('display_errors', false);
            // enable logging to file
            ini_set("log_errors", true);
            ini_set("error_log", $this->log_file);
        }
        else {
            // enable error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', true);
            // disable logging to file
            ini_set("log_errors", false);
        }

        // setup error and exception handlers
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
    }

    public function exceptionHandler($exception)
    {
        if ($this->environment === 'production') {
            error_log($exception, 3, $this->log_file);
        }

        throw new Exception('', null, $exception);
    }

    public function errorHandler($error_level, $error_message, $error_file, $error_line)
    {
        if ($this->environment === 'production') {
            error_log($message, 3, $this->log_file);
        }

        throw new ErrorException($error_message, 0, $error_level, $error_file, $error_line);
    }
}
?>
