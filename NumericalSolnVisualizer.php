<?php

// MAIN

// Check if prop file is passed in
if (empty($argv[1])) {
    echo "\nUsage: $argv[0] config.ini\n";
    exit(1);
}

$config = parse_ini_file($argv[1], true);

$visualizer = new SolutionVisualizer($config);

$logHandle = fopen($config['logFileName'], 'r');

$solutions = array();
$pcount = 0;
$visualizer->generateGraph(0, null);

while (!feof($logHandle)) {
    $str = fgets($logHandle);

    if (empty($str)) break;

    $arr = explode(",", $str);
    $count = $arr[0];

    if ($pcount > 0 && $pcount != $count) {
        $visualizer->generateGraph($pcount, $solutions);
        $solutions = array();
    }

    array_push($solutions, [$arr[1], $arr[2]]);
    $pcount = $count;
}
$visualizer->generateGraph($pcount, $solutions);

fclose($logHandle);


class SolutionVisualizer
{

    //Set the image width and height
    private $gLeft, $gBottom;
    private $gTop,  $gRight;

    private $lowerXLimit, $lowerYLimit;
    private $upperXLimit, $upperYLimit;

    private $gWidth, $gHeight;
    private $outfilePrefix, $templateFile;

    private $numCount = 0;

    function __construct(array $config)
    {
        $this->gLeft    = $config['Visualizer']['gLeft'];
        $this->gBottom  = $config['Visualizer']['gBottom'];
        $this->gTop     = $config['Visualizer']['gTop'];
        $this->gRight   = $config['Visualizer']['gRight'];

        $this->gWidth   = $config['Visualizer']['gWidth'];
        $this->gHeight  = $config['Visualizer']['gHeight'];

        $this->lowerXLimit = $config['Visualizer']['lowerXLimit'];
        $this->upperXLimit = $config['Visualizer']['upperXLimit'];
        $this->lowerYLimit = $config['Visualizer']['lowerYLimit'];
        $this->upperYLimit = $config['Visualizer']['upperYLimit'];

        $this->outfilePrefix = $config['Visualizer']['outfilePrefix'];
        $this->templateFile  = $config['Visualizer']['templateFile'];

    }

    private $pSolutions = array();

    function generateGraph($count, $solutions)
    {
        global $gX0, $gY0, $gXM, $gYM;
        //Create the image resource 
        $image = imagecreatefromgif($this->templateFile);
        //ImageCreate($width, $height);  
        //imagetruecolortopalette($image, false, 256);
        $colBorder = $colSol = imagecolorallocate($image, 0,0,0);
        //$colBorder = imagecolorat()
        //$grey        = ImageColorat($image, 431, 255);
        //$grey   = ImageColorAllocate($image, 218, 218, 218);
       /* // Sphere 2D Colors
        $colActive   = imagecolorat($image, 433, 355);
        $colInactive = imagecolorat($image, 433, 282);
        $colMove = imagecolorat($image, 433, 355);
        $colGrid = imagecolorat($image, 433, 89);
        */

        // CrossinTray colors
        $colActive   = imagecolorat($image, 474, 355);
        $colInactive = imagecolorat($image, 474, 282);
        $colMove = imagecolorat($image, 474, 355);
        $colGrid = imagecolorat($image, 474, 89);


        //$dorange = ImageColorAllocate($image, 128, 0, 0);
        //$orange = ImageColorAllocate($image, 128 + 64, 64, 64);
        //$lorange = ImageColorAllocate($image, 255, 128, 128);

        //$white  = ImageColorAllocate($image, 255, 255, 255);
        //$black  = ImageColorAllocate($image, 0, 0, 0);


        // This will draw solution circles
        
        /*


        foreach (range($this->lowerXLimit, $this->upperXLimit) as $c) {
            $this->drawLine($image, $c, $this->lowerYLimit, $c, $this->upperYLimit, $colGrid);
            $this->drawLine($image, $this->lowerXLimit, $c, $this->upperXLimit, $c, $colGrid);
        }
*/
        if ($count > 0) {

           // $this->drawCircle($image, [0.5,0.5] ,20, $colSol);
            /*
            $xs = array();
            $ys = array();

            foreach ($this->pSolutions as $solution) {

                $xs[number_format($solution[0],4)] = $solution[1];
                $ys[number_format($solution[1],4)] = $solution[0];
            }

            foreach ($solutions as $solution) {
                if (array_key_exists(number_format($solution[0],4), $xs)) {
                    $this->drawLine($image, $solution[0], $solution[1], $solution[0], $xs[number_format($solution[0],4)], $orange);
                } elseif  (array_key_exists(number_format($solution[1],4), $ys)) {
                    $this->drawLine($image, $solution[0], $solution[1], $ys[number_format($solution[1],4)], $solution[1], $orange);
                }
            }
            */
            foreach ($solutions as $i => $solution) {
                if (!empty($this->pSolutions[$i])) {
                    if ($solution[0] == $this->pSolutions[$i][0] || $solution[1] == $this->pSolutions[$i][1])
                        $this->drawLine($image, $solution[0], $solution[1], $this->pSolutions[$i][0], $this->pSolutions[$i][1], $colMove);
                    else {
                        $this->drawPoint($image, $this->pSolutions[$i], $colInactive);
                    }
                }
            }

            foreach ($solutions as $solution) {
                $this->drawPoint($image, $solution, $colActive);
            }



            //list($x0, $y0) = $this->getCoords($gX0, $gY0);
            //list ($x1, $y1) = $this->getCoords($gXM, $gYM);
            //imagerectangle($image, $x0, $y0, $x1, $y1, $orange);
            //print_r($solutions);
        } elseif ($count == 0) {
            imagestring($image, 5, $this->gWidth / 2 - 20, $this->gHeight / 2, "START", $colBorder);
        }

        imagestring($image, 3, 10, $this->gHeight - 20, "Cycle $count", $colBorder);
        imagestring($image, 3, $this->gWidth-120, $this->gHeight - 20, "aaxisdigital.com", $colBorder);
        $fileName = sprintf("%s_%03d.gif", $this->outfilePrefix, $count);

        imagegif($image, $fileName);
        //exit(1);
        $this->pSolutions = $solutions;
    }

    function getCoords($x, $y)
    {        
        return [
            $this->gLeft +  ($this->gRight - $this->gLeft) * ($x - $this->lowerXLimit) / ($this->upperXLimit - $this->lowerXLimit),
            $this->gBottom -  ($this->gBottom - $this->gTop) * ($y - $this->lowerYLimit) / ($this->upperYLimit - $this->lowerYLimit)
        ];
    }

    function drawLine($image, $x0, $y0, $x1, $y1, $color)
    {

        list($cx0, $cy0) = $this->getCoords($x0, $y0);
        list($cx1, $cy1) = $this->getCoords($x1, $y1);
        //print("$x0, $y0, $x1, $y1 :: $cx0, $cy0, $cx1, $cy1\n");
        imageline($image, $cx0, $cy0, $cx1, $cy1, $color);
    }

    function drawPoint($image, $coords, $color)
    {
        list($cx0, $cy0) = $this->getCoords($coords[0], $coords[1]);
        //echo "$coords[0], $coords[1] = $cx0, $cy0\n";
        imagefilledellipse($image, $cx0, $cy0, 10, 10, $color);
    }

    function drawCircle($image, $coords, $width, $color)
    {
        list($cx0, $cy0) = $this->getCoords($coords[0], $coords[1]);
        //echo "$coords[0], $coords[1] = $cx0, $cy0\n";
        imageellipse($image, $cx0, $cy0, $width, $width, $color);
    }
}
