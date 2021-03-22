<?php

// MAIN

// Check if prop file is passed in
if (empty($argv[1])) {
    echo "\nUsage: $argv[0] config.ini\n";
    exit(1);
}

$config = parse_ini_file($argv[1], true);

$visualizer = new RailCarSolutionVisualizer($config);

$logHandle = fopen($config['logFileName'], 'r');

$solutions = array();
$bestSoln = 1000;
$pcount = 0;

while (!feof($logHandle)) {
    $str = fgets($logHandle);

    if (empty($str)) break;

    $arr = explode(",", $str);
    $count = $arr[0];
    
    if ($pcount > 0 && $pcount != $count) {
        $visualizer->generateGraph($pcount, $solutions);

        print(implode(",",$solutions)."\n");
        print("BEST = $bestSoln\n");
        $solutions = array();
        $bestSoln = 1000;
        //exit(1);
    }
    $dim = count($arr);
    //print_r($arr);
    $soln = intval($arr[$dim-1]);
    //print "$dim :$arr[16]: ". $arr[$dim-1]." < $bestSoln = " . ( $arr[$dim-1] < $bestSoln )."\n";
    if ($soln < $bestSoln) {
        $solutions = $arr;
        $bestSoln = $arr[$dim-1];
        //print "--- " . implode(":",$arr);
    } 
    $pcount = $count;

}
$visualizer->generateGraph($pcount, $solutions);

fclose($logHandle);


class RailCarSolutionVisualizer
{

    //Set the image width and height
    private $gLeft, $gBottom;
    private $gTop,  $gRight;

    private $lowerXLimit, $lowerYLimit;
    private $gridXCount, $gridYCount;


    private $gWidth, $gHeight;
    private $outfilePrefix, $templateFile;

    private $numCount = 0;
    private $stackArray = array();
    private $stackCounts = array();
    private $dimensions = 0;

    function __construct(array $config)
    {
        $this->gLeft    = $config['Visualizer']['gLeft'];
        $this->gBottom  = $config['Visualizer']['gBottom'];
        $this->gTop     = $config['Visualizer']['gTop'];
        $this->gRight   = $config['Visualizer']['gRight'];

        $this->gWidth     = $config['Visualizer']['gWidth'];
        $this->gHeight    = $config['Visualizer']['gHeight'];
        $this->gridXCount = $config['Visualizer']['gridXCount'];
        $this->gridYCount = $config['Visualizer']['gridYCount'];


        $this->outfilePrefix = $config['Visualizer']['outfilePrefix'];
//        $this->templateFile  = $config['Visualizer']['templateFile'];

        $this->parseStacks($config['FoodSource']);
        $this->dimensions = $config['FoodSource']['dimensions'];

    }

    private $pSolutions = array();

    function generateGraph($count, $solutions)
    {
        global $gX0, $gY0, $gXM, $gYM;

        list($fsCounts, $fnValue) = $this->stackCounts($solutions);

        $image  = imagecreatetruecolor($this->gWidth, $this->gHeight);
        imagefilledrectangle($image, 0, 0, $this->gWidth, $this->gHeight, 0xFFFFFF);
        
        $colBorder = imagecolorallocate($image, 0,0,0);
        $colGrid   = imagecolorallocate($image, 128,128,128);
        $colInactive = imageColorallocate($image, 64,64,64);
        $colActive  = imagecolorallocate($image, 255,128,0);
        $colError   = imagecolorallocate($image, 255,0,0);
        $colText    = imagecolorallocate($image, 0,128,0);

        $box = [$this->gLeft, $this->gTop, $this->gRight, $this->gBottom];

        imagerectangle($image, $box[0], $box[1], $box[2], $box[3], $colBorder);

        // X grid
        $dwidth = ($box[2]-$box[0])/$this->gridXCount;

        $delta = $this->stackCounts[0][0]-$fsCounts[0][0];
        imagestring($image,4, $box[0] + 0.5*$dwidth, $box[3]+10, $fsCounts[0][0],$delta==0?$colText:$colError);
        imagestring($image,1, $box[0] + 0.5*$dwidth, $box[3]+30, ($delta==0?"":$delta), $colActive);
        foreach (range(1, $this->gridXCount-1) as $c) {
            $x = $box[0] + $c*$dwidth;
            imageline($image,$x,$box[1],$x,$box[3], $colGrid);

            $delta = $this->stackCounts[0][$c]-$fsCounts[0][$c];
            imagestring($image,4, $x + 0.5*$dwidth, $box[3]+10, $fsCounts[0][$c],$delta==0?$colText:$colError);
            imagestring($image,1, $x + 0.5*$dwidth, $box[3]+30, ($delta==0?"":$delta), $colError);
        }

        // Y grid
        $dwidth = ($box[3]-$box[1])/$this->gridYCount;

        $delta = $this->stackCounts[1][0]-$fsCounts[1][0];
        imagestring($image,4, $box[3]+10, $box[2] - 0.5*$dwidth, $fsCounts[1][0],$delta==0?$colText:$colError);
        imagestring($image,1, $box[3]+25, $box[2] - 0.5*$dwidth+4, ($delta==0?"":$delta), $colActive);
        foreach (range(1, $this->gridYCount-1) as $c) {
            $y = $box[1] + $c*($box[3]-$box[1])/$this->gridYCount;
            imageline($image,$box[0],$y,$box[2],$y,$colGrid);

            $delta = $this->stackCounts[1][$this->gridYCount-$c]-$fsCounts[1][$this->gridYCount-$c];
            imagestring($image,4, $box[3]+10, $y - 0.5*$dwidth, $fsCounts[1][$this->gridYCount-$c],$delta==0?$colText:$colError);
            imagestring($image,1, $box[3]+25, $y - 0.5*$dwidth+4, ($delta==0?"":$delta), $colActive);
            //imagestring($image,1, $box[3]-10, $y - 0.5*$dwidth+4,$this->gridYCount-$c, $colActive);
        }

        foreach(range(0, $this->dimensions-1) as $stackNum){
            $this->renderStack($image, $box, $stackNum, $solutions[$stackNum+1]);
        }

        imageString($image,4, $box[2]+10, $box[3]+10,"$fnValue", $colActive);
        imageString($image,4, $box[0]+10, $box[3]+30,"Iteration $count", $colActive);


        $fileName = sprintf("%s_%03d.gif", $this->outfilePrefix, $count);
        
        imagegif($image, $fileName);
        //exit(1);
        //$this->pSolutions = $solutions;

    }

    private function renderStack ($image, $box, $stackNum, $burnCount) {

        //print ("+++++++++++ $stackNum, $burnCount\n");
        $colInactive = imageColorallocate($image, 64,64,64);
        $colActive  = imagecolorallocate($image, 255,128,0);


        $part = 1;
        $length = count($this->stackArray[$stackNum]);
        $pPos = null;
        foreach ($this->stackArray[$stackNum] as $hCount=>$pos) {
            if ($hCount == $length-1) { $part = -1;}
            if ($hCount == 0 ) { $pPos = $this->stackArray[$stackNum][1]; }
            $this->renderOneBox($image, $box, $pPos, $pos, $hCount<$burnCount?1:0, $part, $stackNum);
            $part = 0;
            $pPos = $pos;
        }
        
    }
    
    protected function renderOneBox($image, $box, $pPos, $pos, $active, $part, $stackNum) {
        $coords = $this->getCoords($box, $pos[0], $pos[1]);
        $pCoords = $this->getCoords($box, $pPos[0], $pPos[1]);
        
        $colA  = imagecolorallocate($image, 255,128,0);
        $colI = imageColorallocate($image, 64,64,64);
        $colIf = imagecolorallocate($image, 241,241,241);
        $colText = imagecolorallocate($image, 0,0,0);
        $col = $active?$colA:$colI;
        $colF = $active?$colA:$colIf;
        
        $boxWidthX = abs($coords[0] - $pCoords[0]);
        $boxWidthY = abs($coords[1] - $pCoords[1]);

        $boxThickX = $boxWidthX>0?0:5;
        $boxThickY = $boxWidthY>0?0:5;
        

        if ($part==1) {  
           

            imagefilledrectangle($image, $coords[0]-$boxThickX, $coords[1]-$boxThickY, 
                  ($coords[0] + $pCoords[0])/2.0+$boxThickX, ($coords[1] + $pCoords[1])/2.0+$boxThickY, $colF);
            
            imagerectangle($image, $coords[0]-$boxThickX, $coords[1]-$boxThickY, 
                  ($coords[0] + $pCoords[0])/2.0+$boxThickX, ($coords[1] + $pCoords[1])/2.0+$boxThickY, $col);

             // Head
            imagefilledellipse($image, $coords[0], $coords[1], 30, 30, $colF);
            imageellipse($image, $coords[0], $coords[1], 30, 30, $col);
            
        } elseif ($part == 0) {
            imagefilledrectangle($image, $coords[0] - $boxThickX - $boxWidthX/2, $coords[1] - $boxThickY - $boxWidthY/2, 
                $coords[0] + $boxThickX + $boxWidthX/2, $coords[1]+ $boxThickY+ $boxWidthY/2, $colF);

            imagerectangle($image, $coords[0] - $boxThickX - $boxWidthX/2, $coords[1] - $boxThickY - $boxWidthY/2, 
                $coords[0] + $boxThickX + $boxWidthX/2, $coords[1]+ $boxThickY+ $boxWidthY/2, $col);

        } else {
            // Tail
            imagefilledrectangle($image, $coords[0]-$boxThickX, $coords[1]-$boxThickY, 
                  ($coords[0] + $pCoords[0])/2.0+$boxThickX, ($coords[1] + $pCoords[1])/2.0+$boxThickY, $colF);

            imagerectangle($image, $coords[0]-$boxThickX, $coords[1]-$boxThickY, 
                  ($coords[0] + $pCoords[0])/2.0+$boxThickX, ($coords[1] + $pCoords[1])/2.0+$boxThickY, $col);
        }
       // imagestring($image, 2,$coords[0], $coords[1], $stackNum, $colText);
        
    }

    function getCoords($box, $x, $y)
    {        
        return [
            $box[0] +  ($box[2]-$box[0]) * (0.5+$x) / $this->gridXCount,
            $box[3] -  ($box[3] - $box[1]) * (0.5+$y) / $this->gridYCount
        ];
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

    public function stackCounts($solutions) {
        
        // Stack config
        $stacks = $this->stackArray;
        $stackCounts = $this->stackCounts;
        $dimensions = $this->dimensions;
        $fsCount = array();
        $fnValue = 0;
        $fsCount[0] = array_fill(0, count($stackCounts[0]), 0);
        $fsCount[1] = array_fill(0, count($stackCounts[1]), 0);

        // Calcualate how off are we in terms of Row and Col usage
        foreach (range(0, $dimensions - 1) as $dim) {
            $burnVal = $solutions[$dim+1];
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
        

        
        return [$fsCount, $fnValue];
    }



}
