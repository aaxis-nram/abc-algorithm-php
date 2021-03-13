<?php
namespace ABCAlgorithm;

$codeRoot = dirname(__FILE__).'/src/ABCAlgorithm/';

//require_once('src/ABCAlgorithm/BeeColony.php');
//require_once('src/ABCAlgorithm/FoodSource.php');

// Check if prop file is passed in
if (empty($argv[1])) {
    echo "\nUsage: $argv[0] <<config.ini>>\n";
    exit(1);
}

// global varibles

// Load stack config from prop file
$iniconfig = parse_ini_file($argv[1], true);
echo "Solving: ". $iniconfig['description']."\n";

//Which class do we need for BeeColony
$beeColonyClass = $iniconfig['BeeColony']['class'];

require_once($codeRoot.$beeColonyClass.'.php');

$class = new \ReflectionClass('\\ABCAlgorithm\\'.$beeColonyClass);
$beeColony = $class->newInstanceArgs([$iniconfig]);

$bestFoodSources = $beeColony->run()
                      ->getBestFoodSources();

echo "Colony simulation with ABC finished. Result:\n";
//print_r($bestFoodSources);

foreach ($bestFoodSources as $foodSource) {
    echo $foodSource->getPrintable(), "\n";
}

