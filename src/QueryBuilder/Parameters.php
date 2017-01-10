<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder;

class Parameters
{
    protected $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function delete($key)
    {
        unset($this->parameters[$key]);

        return $this;
    }

    public function has($key)
    {
        return isset($this->parameters[$key]);
    }

    public function set($key, $value)
    {
        $this->parameters[$key] = $value;

        return;
    }

    public function get($key, $default = null)
    {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : $default;
    }

    public function all()
    {
        return $this->parameters;
    }

    public function replace($parameters)
    {
        $this->parameters = [];
        $this->merge($parameters);

        return $this;
    }

    public function merge($parameters)
    {
        if(is_array($parameters))
            $array = $parameters;
        elseif($parameters instanceof Parameters)
            $array = $parameters->all();
        else
            throw new Exception('Adapter parameters must be an array of Parameters instance.');

        $this->parameters = array_merge($this->parameters, $array);

        return $this;
    }
}
