<?php
# The MIT License
#
# Copyright (c) 2021 Naresh Ram (info at aaxisdigital dot com)
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

namespace ABCAlgorithm;

require_once('Conditionable.php');

/**
 * Food Source class encapsulates a food source. It has the parameters and nectar amount
 * Utility functions such as getRandom and getNeighboring are provided as well.
 */
abstract class AbstractFoodSource
{
    // Userful traits
    use Conditionable;

    // Configuration
    protected $dimensions = 2;              // Dimensions of the solution
    protected $lowerLimit = -1.0;           // Lower Limit
    protected $upperLimit = +1.0;           // Upper Limit

    // members
    private $fnVal = 0.0;
    private $nectar = -1000.0;              // nectar amount for this food source
    private $probability = 0.0;             // This is the probability of selection based on nectar of all locations currently remembered by colony
    private $trialCount = 0;                // How many times has this location been evaluated.
    private $identifier = "";               // A unique identifier for this solution
                                            // If two FoodSources have the same id, they are the swame
    // parameters of the solution representing the location of the food source
    private $parameters = array();
    
    private static $logFileName;
    private static $logHandle;

    // this abstract method must be implemented
    /**
     * Evaluate Nectar evaluates the nectar value and sets the private nectar variable.
     *  
     * @return self
     */
    abstract public function functionValue(): float;


    /**
     * Public constructor. Get a new FoodSource either as random or as neighboring
     *
     * @param array $parameters
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this
            ->setPropertyIfNotEmpty($config['dimensions'], 'setDimensions')
            ->setPropertyIfNotEmpty($config['lowerLimit'], 'setLowerLimit')
            ->setPropertyIfNotEmpty($config['upperLimit'], 'setUpperLimit')
            ->initialize();

            if (empty(self::$logFileName) && !empty($config['logFileName'])) {
                self::setLogFileName($config['logFileName']);
            }
    }

    function __clone()
    {
        //$this->setParameters(array_fill(0,$this->getDimensions(), $this->getLowerLimit()));
    }

    /**
     * Evaluates the source and gets the nectar amount
     * This is an oppurtunity to adjust the food source
     *
     * @param array $parameters
     * @return float the nectar amount
     */
    public function evaluateNectar(): float {
        $this->setFnVal($this->functionValue());
        
        $nectar = 0;
        if ($this->getFnVal()>0) {
            $nectar =  1.0/(1.0 + $this->getFnVal());
        } else {
            $nectar = 1+abs($this->getFnVal());
        }
        return $nectar;
    }


    /**
     * Defualt intializer gives random values lowerLimit to upperLimit
     *
     * @return self
     */
    public function initialize()
    {
        $parameters = array();
        foreach (range(0, $this->getDimensions() - 1) as $dim) {
            $parameters[$dim] = $this->getLowerLimit() 
                                    + (mt_rand()/mt_getrandmax())*($this->getUpperLimit() - $this->getLowerLimit());
        }
        $this->setParameters($parameters);
        return $this;
    }

    /**
     * Get new FoodSource from an old one with a random modification to a random dimension
     *
     * @param AbstractFoodSource $neighborFoodsource
     * @return AbstractFoodSource
     */
    public function getNeighoring(AbstractFoodSource $neighborFoodsource): AbstractFoodSource {
        $parameters = $this->getParameters();
        
        $dimensions = count($parameters);
        
        $dim = mt_rand(0,$dimensions-1);

        // Amount of impact is between -1 to 1
        $phi = -1.0 + (mt_rand()/mt_getrandmax())*2.0;
        //echo ("PHI is $phi\n");
        $newParam = $parameters[$dim] 
                    + $phi*($parameters[$dim] - $neighborFoodsource->getParameters()[$dim] );

        // make sure $newParam is between upper and lower limits;
        $newParam = min(max($newParam, $this->getLowerLimit()), $this->getUpperLimit());
        $parameters[$dim] = $newParam;

        return (clone $this)->setParameters($parameters);
    }

    /**
     * Get the value of nectar
     * 
     * @return float nectar quantity
     */
    public function getNectar():float
    {
        return $this->nectar;
    }

    /**
     * Sets the value of nectar
     * 
     * @return self 
     */
    public function setNectar($nectar)
    {
        $this->nectar = $nectar;
        return $this;
    }

    /**
     * Gets printable string of the food source
     *
     * @return string
     */
    public function getPrintable(): string
    {
        $str = "f(";
        foreach ($this->getParameters() as $p) {
            $str .= sprintf("%.4f,", $p);
        }
        $str = substr($str, 0, -1).") = ". sprintf("%.4f", $this->getFnVal());
        return $str;
    }

    public function getPrintableCSV(): string
    {
        $str = "";
        foreach ($this->getParameters() as $p) {
            $str .= sprintf("%.4f,", $p);
        }
        $str .= sprintf("%.4f,%.4f",$this->getFnVal(), $this->getNectar());
        return $str;
    }

    public function logData($cycle) {
        self::logDataMessage($cycle.",".$this->getPrintableCSV());
    }

    /**
     * Get the value of parameters
     */ 
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set the value of parameters. This function also evaluates the nectar.
     *
     * @return  self
     */ 
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        $this->setNectar($this->evaluateNectar());
        $this->setIdentifier($this->generateIdentifier());
        return $this;
    }

    /**
     * This function returns a string representation of the solution
     * If two solutions are considered equal, they should return the same Id
     * Could be over ridden.
     *
     * @return string
     */
    public function generateIdentifier():string {
        $str = "";
        foreach ($this->getParameters() as $p) {
            $str .= sprintf("%.0f,", $p);
        }
        $str = substr($str, 0, -1);
        return $str;
    }

    /**
     * Get the value of trialCount
     */ 
    public function getTrialCount()
    {
        return $this->trialCount;
    }

    /**
     * Increments the value of trialCount by 1
     *
     * @return  self
     */ 
    public function incrementTrialCount()
    {
        $this->trialCount++;

        return $this;
    }

    /**
     * Reset the trial count
     *
     * @return self
     */
    public function resetTrialCount()
    {
        $this->trialCount = 0;

        return $this;
    }
    /**
     * Get the value of probability
     */ 
    public function getProbability()
    {
        return $this->probability;
    }

    /**
     * Set the value of probability
     *
     * @return  self
     */ 
    public function setProbability($probability)
    {
        $this->probability = $probability;

        return $this;
    }


    /**
     * Get the value of identifier
     */ 
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set the value of identifier
     *
     * @return  self
     */ 
    protected function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Set the value of dimensions
     *
     * @return  self
     */ 
    protected function setDimensions(int $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * Get the value of dimensions
     */ 
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Get the value of lowerLimit
     */ 
    public function getLowerLimit()
    {
        return $this->lowerLimit;
    }

    /**
     * Set the value of lowerLimit
     *
     * @return  self
     */ 
    protected function setLowerLimit($lowerLimit)
    {
        $this->lowerLimit = $lowerLimit;

        return $this;
    }

    /**
     * Get the value of upperLimit
     */ 
    public function getUpperLimit()
    {
        return $this->upperLimit;
    }

    /**
     * Set the value of upperLimit
     *
     * @return  self
     */ 
    protected function setUpperLimit($upperLimit)
    {
        $this->upperLimit = $upperLimit;

        return $this;
    }


    /**
     * Set the value of logFileName
     *
     * @return  
     */ 
    public static function setLogFileName($logFileName)
    {
        self::$logFileName = $logFileName;
    }

    private static function logDataMessage($str) 
    {
        $locked = false;
        if (empty(self::$logHandle) && !$locked) {
            $locked = true;
            if (empty(self::$logFileName)) {
                self::$logFileName = 'log.csv';
            }
            self::$logHandle = fopen(self::$logFileName, "w");
        }
        fwrite(self::$logHandle, $str."\n");
    }

    /**
     * Get the value of fnVal
     */ 
    public function getFnVal()
    {
        return $this->fnVal;
    }

    /**
     * Set the value of fnVal
     *
     * @return  self
     */ 
    private function setFnVal($fnVal)
    {
        $this->fnVal = $fnVal;

        return $this;
    }
}
