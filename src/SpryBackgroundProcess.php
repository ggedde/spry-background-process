<?php

/**
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

namespace Spry\SpryProvider;

use Cocur\BackgroundProcess\BackgroundProcess;
use Spry\Spry;

/**
 *  Provider for Spry Background Processes
 *  Users Cocur\BackgroundProcess\BackgroundProcess.
 */
class SpryBackgroundProcess
{
    /**
     * Creates a background Process calling a Spry Component.
     *
     * @param string     $controller
     * @param array|null $params
     * @param array|null $meta
     * @param array|null $config
     *
     * @return int|array
     */
    public static function create($controller, $params = null, $meta = null, $config = null)
    {
        $args = [
            'config' => $config,
            'controller' => $controller,
            'meta' => $meta,
            'params' => $params,
            'process' => true,
        ];

        $autoloader = self::getAutoloader();

        if (empty($autoloader)) {
            Spry::stop(61);
        }

        if (!empty($args['controller'])) {
            if (!Spry::controllerExists($args['controller'])) {
                Spry::stop(16, null, null, $args['controller']); // Controller Not Found
            }

            if (is_null($args['config'])) {
                $args['config'] = Spry::getConfigFile();
            }

            $cmdComposer = "include '".$autoloader."';";
            $cmdSpry = "Spry\\Spry::run('".base64_encode(json_encode($args))."');";

            $command = 'php -r "'.$cmdComposer.$cmdSpry.'"';
            $command = str_replace(' ', escapeshellcmd(' '), $command);

            $process = new BackgroundProcess($command);
            $process->run();

            $pid = $process->getPid();

            $hash = self::getHash($pid);

            if (empty($pid)) {
                Spry::stop(60);
            }

            return (object) ['pid' => $pid, 'hash' => $hash];
        }

        return null;
    }

    /**
     * Checks to see if a Process is still running.
     *
     * @param int $pid
     *
     * @return int|null
     */
    public static function getHash($pid = 0)
    {
        if (in_array(strtoupper(PHP_OS), ['LINUX', 'FREEBSD', 'DARWIN'])) {
            if ($hash = shell_exec(sprintf('ps -o lstart=,command= %d', $pid))) {
                return md5($hash);
            }
        }

        return '';
    }

    /**
     * Checks to see if a Process is still running.
     *
     * @param int    $pid
     * @param string $hash
     *
     * @return int|null
     */
    public static function isRunning($pid = 0, $hash = '')
    {
        if ($hash) {
            if (strval($hash) === self::getHash($pid)) {
                return 1;
            }

            return 0;
        }

        if ($process = BackgroundProcess::createFromPID($pid)) {
            return $process->isRunning() ? 1 : 0;
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        Spry::log(Spry::response(false, 62)->messages[0].' - createFromPID('.$pid.')');

        return null;
    }

    /**
     * Stops a current running process.
     *
     * @param int $pid
     *
     * @return bool|null
     */
    public static function stop($pid = 0)
    {
        if ($process = BackgroundProcess::createFromPID($pid)) {
            return $process->stop() ? 1 : 0;
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        Spry::log(Spry::response(false, 62)->messages[0].' - createFromPID('.$pid.')');

        return null;
    }

    /**
     * Checks if a process is running and if so then stops it.
     *
     * @param int    $pid
     * @param string $hash
     *
     * @return bool|null
     */
    public static function stopIfIsRunning($pid = 0, $hash = '')
    {
        if ($hash) {
            $getHash = self::getHash($pid);

            if (!$getHash) {
                return 1;
            }

            if ($getHash !== $hash) {
                // Unkown Error from Background Process
                // Log it but don't exit the script
                Spry::log(Spry::response(false, 62)->messages[0].' - Hash does not match.');

                return 0;
            }
        }

        if ($process = BackgroundProcess::createFromPID($pid)) {
            if ($process->isRunning()) {
                return $process->stop() ? 1 : 0;
            }

            // Unkown Error from Background Process
            // Log it but don't exit the script
            Spry::log(Spry::response(false, 62)->messages[0].' - isRunning('.$pid.')');
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        Spry::log(Spry::response(false, 62)->messages[0].' - createFromPID('.$pid.')');

        return null;
    }

    /**
     * Finds the Composer Autoload file.
     *
     * @return string|bool
     */
    public static function getAutoloader()
    {
        $paths = [
            dirname(dirname(__DIR__)),
            dirname(dirname(dirname(__DIR__))),
            dirname(dirname(dirname(dirname(__DIR__)))),
        ];

        foreach ($paths as $path) {
            if (file_exists($path.'/autoload.php')) {
                return $path.'/autoload.php';
            }
        }

        return false;
    }
}
