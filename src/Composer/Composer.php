<?php

namespace Concrete5\Api\Composer;

class Composer extends \Illuminate\Support\Composer
{

    /**
     * Wrap a callable and pass in a process
     * @param array $command
     * @param callable $callable
     * @return mixed
     */
    public function do(array $command, callable $callable)
    {
        $process = $this->getProcess([...$this->findComposer(), ...$command]);
        return $callable(
            $process,
            $this
        );
    }

    public function getWorkingPath(): string
    {
        return $this->workingPath;
    }

    public function prepare(): Composer
    {
        $path = $this->getWorkingPath();
        $check = ['/concrete', '/web/concrete'];

        foreach ($check as $checkPath) {
            if (!$this->files->exists($path . $checkPath)) {
                $this->files->makeDirectory($path . $checkPath, 0755, true, true);
            }
        }

        return $this;
    }

}
