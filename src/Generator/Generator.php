<?php


namespace Concrete5\Api\Generator;


use Doctum\Doctum;

class Generator extends Doctum
{

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($offset === 'renderer') {
            return new Renderer($this['twig'], $this['themes'], $this['tree'], $this['indexer']);
        }

        return parent::offsetGet($offset);
    }

}
