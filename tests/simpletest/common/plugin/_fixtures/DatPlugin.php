<?php

class DatPlugin {

    private $id;

    private $counter = 0;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRandomInteger(array $params)
    {
        $params['random'] = 4;
    }

    public function increment(array $params)
    {
        $params['counter'] = $this->counter++;
    }

    public function setName($name)
    {

    }

    public function setIsRestricted($restricted)
    {

    }
}
