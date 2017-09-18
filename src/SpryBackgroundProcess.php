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
 	 * @return int|null
	 */

    public static function create($args=[])
    {
        $args = array_merge([
            'controller' => '',
            'input' => []
        ], $args);

        $autoloader = self::getAutoloader();

        if(empty($autoloader))
        {
            Spry::stop(5061);
        }

        if(!empty($args['controller']))
        {
            $cmd_composer = "include '".$autoloader."';";
            $cmd_spry = "Spry\\Spry::run('".Spry::get_config_file()."', '".$args['controller']."');";
            $cmd_input = json_encode($args['input']);

            $command = 'echo \''.$cmd_input.'\' | php -r "'.$cmd_composer.$cmd_spry.'"';

            $command = str_replace(' ', escapeshellcmd(" "), $command);

            $process = new BackgroundProcess($command);
            $process->run();

            $pid = $process->getPid();

            if(empty($pid))
            {
                Spry::stop(5060);
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

    public static function isRunning($pid=0)
    {
        if($process = BackgroundProcess::createFromPID($pid))
        {
            return ($process->isRunning() ? 1 : 0);
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
            return $process->stop();
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

    public static function stopIfIsRunning($pid=0)
    {
        if($process = BackgroundProcess::createFromPID($pid))
        {
            if($process->isRunning())
            {
                return $process->stop();
            }
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
