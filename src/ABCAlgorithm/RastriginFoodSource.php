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

class RastriginFoodSource extends AbstractFoodSource
{
    /**
     * Evaluates the source and gets the nectar amount
     * This is an oppurtunity to adjust the food source
     *
     * @param array $parameters
     * @return float the nectar amount
     */

    public function functionValue(): float {
        $x = $this->getParameters();
        
        //$fnVal = pow( (pow($x,2) + $y -11), 2) + pow( ($x + pow($y,2) -7), 2);
        $A = 10;
        $dim = 2;
        $fnVal = $A*$dim;
        foreach ($x as $xi) {
            $fnVal += (  pow($xi,2) - $A*cos( 2.0 * pi() * $xi )  ) ;
        } 
        return $fnVal;
    }

    public function generateIdentifier():string {
        $str = "";
        foreach ($this->getParameters() as $p) {
            $str .= round($p,0).",";
        }
        $str = substr($str, 0, -1);
        return $str;
    }
}