<?php

namespace Concrete5\Api\Composer;

class Composer extends \Illuminate\Support\Composer
{

    /**
     * Wrap a callable and pass in a process
     * @param callable $callable
     * @return mixed
     */
    public function do(callable $callable)
    {
        return $callable($this->getProcess(), $this->findComposer(), $this);
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
