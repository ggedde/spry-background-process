<?php

namespace Spry\SpryProvider;

use Spry\Spry;
use Cocur\BackgroundProcess\BackgroundProcess;

/**
 *
 *  Provider for Spry Background Processes
 *  Users Cocur\BackgroundProcess\BackgroundProcess
 *
 */

class SpryBackgroundProcess
{



    /**
	 * Creates a background Process calling a Spry Component
	 *
 	 * @param array $args
 	 *
 	 * @access 'public'
 	 * @return int|array
	 */

    public static function create($args=[])
    {
        $args = array_merge([
            'controller' => '',
            'params' => [],
            'hash' => false
        ], $args);

        $autoloader = self::getAutoloader();

        if(empty($autoloader))
        {
            Spry::stop(5061);
        }

        if(!empty($args['controller']))
        {
            if(!Spry::controller_exists($args['controller']))
            {
                Spry::stop(5016, null, $args['controller']); // Controller Not Found
            }

            $args['config'] = Spry::get_config_file();

            $cmd_composer = "include '".$autoloader."';define('SPRY_DIR', '".SPRY_DIR."');";
            $cmd_spry = "Spry\\Spry::run('".base64_encode(json_encode($args))."');";

            $command = 'php -r "'.$cmd_composer.$cmd_spry.'"';
            $command = str_replace(' ', escapeshellcmd(" "), $command);

            $process = new BackgroundProcess($command);
            $process->run();

            $pid = $process->getPid();

            $hash = self::getHash($pid);

            if(empty($pid))
            {
                Spry::stop(5060);
            }

            if($args['hash'])
            {
                return ['pid' => $pid, 'hash' => $hash];
            }

            return $pid;
        }

        return null;
    }



    /**
	 * Checks to see if a Process is still running
	 *
 	 * @param int $pid
 	 *
 	 * @access 'public'
 	 * @return int|null
	 */

    public static function getHash($pid=0)
    {
        if(in_array(strtoupper(PHP_OS), ['LINUX', 'FREEBSD', 'DARWIN']))
        {
            if($hash = shell_exec(sprintf('ps -o lstart=,command= %d', $pid)))
            {
                return md5($hash);
            }
        }

        return '';
    }



    /**
	 * Checks to see if a Process is still running
	 *
 	 * @param int $pid
 	 *
 	 * @access 'public'
 	 * @return int|null
	 */

    public static function isRunning($pid=0, $hash='')
    {
        if($hash)
        {
            if($hash === self::getHash($pid))
            {
                return 1;
            }

            return 0;
        }

        if($process = BackgroundProcess::createFromPID($pid))
        {
            return ($process->isRunning() ? 1 : 0);
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        if(!empty(Spry::config()->response_codes[5062]['en']))
        {
            Spry::log(Spry::config()->response_codes[5062]['en'].' - createFromPID('.$pid.')');
        }

        return null;
    }



    /**
	 * Stops a current running process
	 *
 	 * @param int $pid
 	 *
 	 * @access 'public'
 	 * @return bool|null
	 */

    public static function stop($pid=0)
    {
        if($process = BackgroundProcess::createFromPID($pid))
        {
            return ($process->stop() ? 1 : 0);
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        if(!empty(Spry::config()->response_codes[5062]['en']))
        {
            Spry::log(Spry::config()->response_codes[5062]['en'].' - createFromPID('.$pid.')');
        }

        return null;
    }



    /**
	 * Checks if a process is running and if so then stops it
	 *
 	 * @param int $pid
 	 *
 	 * @access 'public'
 	 * @return bool|null
	 */

    public static function stopIfIsRunning($pid=0, $hash='')
    {
        if($hash)
        {
            $get_hash = self::getHash($pid);

            if(!$get_hash)
            {
                return 1;
            }

            if($get_hash !== $hash)
            {
                // Unkown Error from Background Process
                // Log it but don't exit the script
                if(!empty(Spry::config()->response_codes[5062]['en']))
                {
                    Spry::log(Spry::config()->response_codes[5062]['en'].' - Hash does not match.');
                }

                return 0;
            }
        }

        if($process = BackgroundProcess::createFromPID($pid))
        {
            if($process->isRunning())
            {
                return ($process->stop() ? 1 : 0);
            }

            // Unkown Error from Background Process
            // Log it but don't exit the script
            if(!empty(Spry::config()->response_codes[5062]['en']))
            {
                Spry::log(Spry::config()->response_codes[5062]['en'].' - isRunning('.$pid.')');
            }
        }

        // Unkown Error from Background Process
        // Log it but don't exit the script
        if(!empty(Spry::config()->response_codes[5062]['en']))
        {
            Spry::log(Spry::config()->response_codes[5062]['en'].' - createFromPID('.$pid.')');
        }

        return null;
    }



    /**
	 * Finds the Composer Autoload file
 	 *
 	 * @access 'public'
 	 * @return string|bool
	 */

    public static function getAutoloader()
    {
        $paths = [
            dirname(dirname(__DIR__)),
            dirname(dirname(dirname(__DIR__))),
            dirname(dirname(dirname(dirname(__DIR__)))),
        ];

        foreach($paths as $path)
        {
            if(file_exists($path.'/autoload.php'))
            {
                return $path.'/autoload.php';
            }
        }

        return false;
    }
}
