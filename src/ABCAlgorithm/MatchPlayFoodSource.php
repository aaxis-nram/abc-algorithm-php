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

require_once('AbstractFoodSource.php');

class MatchPlayFoodSource extends AbstractFoodSource
{
    private $stackArray = array();
    private $stackCounts = array();

    public function __construct(array $config)
    {   
        $this->parseStacks($config);
        parent::__construct($config);

        // need to handle the board

    }
    /**
     * Evaluates the source and gets the nectar amount
     * This is an oppurtunity to adjust the food source
     *
     * @param array $parameters
     * @return float the nectar amount
     */
    public function functionValue(): float {
        
        // Stack config
        $stacks = $this->stackArray;
        $stackCounts = $this->stackCounts;
        $dimensions = $this->getDimensions();
        $fsCount = array();
        $fnValue = 0;
        $fsCount[0] = array_fill(0, count($stackCounts[0]), 0);
        $fsCount[1] = array_fill(0, count($stackCounts[1]), 0);

        // Calcualate how off are we in terms of Row and Col usage
        foreach (range(0, $dimensions - 1) as $dim) {
            $burnVal = $this->getBurnVal($dim);
            if ($burnVal > 0) {
                foreach (range(0, $burnVal - 1) as $sNum) {
                    if ($sNum < count($stacks[$dim])) {
                        // Now assign the burnt segment to row and col
                        $fsCount[0][$stacks[$dim][$sNum][0]]++;
                        $fsCount[1][$stacks[$dim][$sNum][1]]++;
                    }
                }
            }
        }
        

        // RMS Difference between R & C values
        foreach (range(0,1) as $c) {
            foreach (range(0, count($fsCount[$c]) - 1) as $rNum) {
                $fnValue += pow($stackCounts[$c][$rNum] - $fsCount[$c][$rNum], 2);
            }
        }
        
        /*
        // Return nectar amount [0,1]
        $this->nectar =  1.0 / (1.0 + $fnValue);
        */

        return $fnValue;
    }


    private function parseStacks(&$config)
    {
        // runs before construct
        $dimensions = 0;

        $stackArray = array();
        $upperLimit = 0;
        foreach ($config['stack'] as $envRow) {
            $splitEnvRow = explode(",", $envRow);
            $dimensions++;
            $row = array();
            $thisStackCells = 0;
            foreach ($splitEnvRow as $splitEnvCell) {
                $cell = array();
                array_push($cell, ord($splitEnvCell[0]) - ord('A'));
                array_push($cell, ord($splitEnvCell[1]) - ord('1'));
                array_push($row, $cell);
                $thisStackCells++;
            }
            array_push($stackArray, $row);
            $upperLimit = max($thisStackCells, $upperLimit);
        }

        $this->stackArray = $stackArray;
        
        $config['dimensions'] = $dimensions;
        /*
        $config['upperLimit'] = $upperLimit;
        $config['lowerLimit'] = 0;
        */

        $this->stackCounts[0] = explode(",", $config['rCounts']);
        $this->stackCounts[1] = explode(",", $config['cCounts']);


        //print_r($this->stackArray);
    }

    public function generateIdentifier():string {
        $str = "";
        foreach (range(0, $this->getDimensions()-1) as $dim) {
            $str .= $this->getBurnVal($dim).",";
        }
        $str = substr($str, 0, -1);
        return $str;
    }

    public function getPrintable(): string
    {
        return "game[ ". $this->getIdentifier(). " ] = ". $this->functionValue();
    }

    public function getPrintableCSV(): string
    {
        return sprintf("%s,%00d ", $this->getIdentifier(), $this->functionValue());
    }

    private function getBurnVal($dim) {
        $stackMax = count($this->stackArray[$dim]);
        $burnVal = min($stackMax, intval((1.0 + $stackMax) * $this->getParameters()[$dim]));

        return $burnVal;
    }

}