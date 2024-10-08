<?php

namespace SimpleScripts\CloudDeployManager\Ssh;

use Illuminate\Support\Facades\Storage;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class PlinkOrProcessSsh extends Ssh
{
    protected CloudwaysApp $cloudwaysApp;

    public function setCloudwaysApp(CloudwaysApp $cloudwaysApp): PlinkOrProcessSsh
    {
        $this->cloudwaysApp = $cloudwaysApp;

        return $this;
    }

    public function isPlink(): bool
    {
        return (bool) config('cw-deploy-manager.ssh.use_plink');
    }

    public function buildPlinkExecuteCommand($command, bool $include_batch = true): string
    {
        $commands = $this->wrapArray($command);
        if ($include_batch) {
            $this->extraOptions[] = '-batch';
        }
        //dd($this->extraOptions);

        $extraOptions = implode(' ', array_values($this->extraOptions));

        $commandString = implode(PHP_EOL, $commands);

        $target = $this->getTargetForSsh();

        if (in_array($this->host, ['local', 'localhost', '127.0.0.1'])) {
            return $commandString;
        }

        /**
         * https://stackoverflow.com/questions/28197540/best-way-to-script-remote-ssh-commands-in-batch-windows
         * build out plink: https://the.earth.li/~sgtatham/putty/0.78/htmldoc/Chapter7.html
         * putty.exe -ssh user@host -i  -m c:\path\command.txt
         */
        $file_name = $this->cloudwaysApp->server->id.'-'.$this->cloudwaysApp->cloudways_server_id.'-app-id-'.$this->cloudwaysApp->id.'.txt';

        if ((bool) config('sshkey.use_temp')) {
            $path = 'C:'.DIRECTORY_SEPARATOR.'Temp'.DIRECTORY_SEPARATOR.$file_name;
            $handle = fopen($path, 'wb');
            fwrite($handle, $commandString);
            fclose($handle);
        } else {
            Storage::disk('local')->put($file_name, $commandString);
            $path = Storage::disk('local')->path($file_name);
        }

        return "plink.exe -ssh {$target} {$extraOptions} ".($include_batch ? ' -m  '.$path : '');
    }

    public function runExec($command): Process|bool|string
    {
        if ($this->isPlink()) {
            // print_r($output);
            return exec($this->buildPlinkExecuteCommand($command));
        }

        return $this->run($command);
    }

    public function uploadLocalFile(string $sourcePath, string $destinationPath): Process|string
    {
        $uploadCommand = $this->getUploadCommand($sourcePath, $destinationPath);

        if ($this->isPlink()) {
            // print_r($output);
            return exec($uploadCommand);
        }

        return $this->upload($sourcePath, $destinationPath);
    }
}
