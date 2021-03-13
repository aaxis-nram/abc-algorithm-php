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

class SphereFoodSource extends AbstractFoodSource
{
    /**
     * Evaluates the source and gets the nectar amount
     * This is an oppurtunity to adjust the food source
     *
     * @param array $parameters
     * @return float the nectar amount
     */
    public function functionValue(): float {
        $fnVal = 0.0;

        // Fn = sum over i : (X^2)
        foreach($this->getParameters() as $x) {
            $fnVal += pow(($x-0.5), 2);
        }
        
        return $fnVal;
    }

}