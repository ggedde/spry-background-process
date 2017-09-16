<?php

namespace Spry\SpryProvider;

use Spry\Spry;
use Cocur\BackgroundProcess\BackgroundProcess;

/**
 *
 *  Generic Log Class to catch API Logs and PHP Error Logs
 *
 */

class SpryBackgroundProcess
{
    public static function create($args=[])
    {
        $args = array_merge(['controller' => '', 'input' => []], $args);

        $vendor_dir = shell_exec('composer config --absolute vendor-dir');

        if(!empty($args['controller']) && !empty($vendor_dir))
        {
            $cmd_composer = "include '".$vendor_dir."/autoload.php';";
            $cmd_spry = "Spry\\Spry::run('".Spry::get_config_file()."', '".$args['controller']."');";
            $cmd_input = json_encode($args['input']);

            $command = 'echo \''.$cmd_input.'\' | php -r "'.$cmd_composer.$cmd_spry.'"';

            $process = new BackgroundProcess($command);
            $process->run();

            return $process->getPid();
        }

        return null;
    }


    public static function isRunning($pid=0)
    {
        if($process = BackgroundProcess::createFromPID($pid))
        {
            return $process->isRunning();
        }

        return null;
    }


    public static function stop($pid=0)
    {
        if($process = BackgroundProcess::createFromPID($pid))
        {
            return $process->stop();
        }

        return null;
    }


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
}
