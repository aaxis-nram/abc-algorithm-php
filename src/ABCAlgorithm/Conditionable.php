<?php

namespace ABCAlgorithm;

trait Conditionable
{
    public function when($condition, $callable)
    {
        if ($condition) {
            $callable($this, $condition);
        }
        return $this;
    }

    public function unless($condition, $callable)
    {
       return $this->when(!$condition, $callable);
    }
    
    public function setPropertyIfNotEmpty($propertyValue, $callableSetter) {
        if (!empty($propertyValue)) {
            $this->$callableSetter($propertyValue);
        }
        return $this;
    }
}