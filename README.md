# spry-background-process
Provider for Spry Background Processes

### Dependencies
Background Processes - https://github.com/cocur/background-process

### Requirements
* PHP 5.4^
* PHP with cli
* function "shell_exec()" available.

## Usage

```php
use Spry\SpryProvider\SpryBackgroundProcess;

$process = [
	'controller' => 'Spry\\SpryComponent\\MyController::myMethod',
	'params' => [
		'id' => 123,
		'name' => 'Something'
	]
];

// Run the Process and return its unix ID
$process_id = SpryBackgroundProcess::create($process);

// Check to see if the process is still running
if(SpryBackgroundProcess::isRunning($process_id))
{
	// It is still running
}
else
{
	// It has finished
}

// Stop a Process by unix ID
SpryBackgroundProcess::stop($process_id);
```
    
## Verify Process with Hash (Recommended)
Unix Process IDs get recycled so it is possible that the same ID may get used for another task.  This could present faulty data when checking the to see if the process is still running OR worse you run the "stop" method on the ID, but the ID was for something else like PHP, Apache, Nginx, Etc which could halt your server entirely.

So a better option would be to verify the process with a Hash of Command and the Time of the command.  
\* *Although this method has not been fully tested on all OS Versions.*

```php
$process = [
	'controller' => 'Spry\\SpryComponent\\MyController::myMethod',
	'hash' => true,
	'params' => [
		'id' => 123,
		'name' => 'Something'
	]
];

// Run the Process and return its unix ID and Hash
$process_data = SpryBackgroundProcess::create($process);

// Check to see if the process is still running
if(SpryBackgroundProcess::isRunning($process_data['pid'], $process_data['hash']))
{
	// It is still running
}
else
{
	// It has finished
}

// Stop a Process by unix ID and Hash
SpryBackgroundProcess::stopIfIsRunning($process_data['pid'], $process_data['hash']);
```
