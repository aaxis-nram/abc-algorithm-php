<?php

namespace ABCAlgorithm;

require_once('AbstractFoodSource.php');
require_once('Conditionable.php');


class BeeColony
{
    use Conditionable;

    // Configuration
    protected $colonySize = 20;             // Size of Colony
    protected $maxCycles = 100;             // Max Cycles 
    protected $numSolutions = 4;            // Number of Solutions to save
    
    protected $foodSourceConfig = array();  // Need this to create a food source.
    protected $maxFoodSources = 0;          // Max Food Sources
    protected $scoutLimit = 0;              // Limit when Scouts find new sources

    // Members
    protected $foodSources = array();       // The foodSources currently remembered
    protected $bestFoodSources = array();   // Best foodsources

    protected $solVis;

    static $logFile;
    /**
     * Constructor. Pass in the config
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $colonyConfig = $config['BeeColony'];
        $config['FoodSource']['logFileName'] = $config['logFileName'];

        $this->setPropertyIfNotEmpty($colonyConfig['maxCycles'], 'setMaxCycles')
            ->setPropertyIfNotEmpty($colonyConfig['colonySize'], 'setColonySize')
            ->setPropertyIfNotEmpty($colonyConfig['numSolutions'], 'setNumSolutions')
            ->setFoodSourceConfig($config['FoodSource']);

        $dimensions = $config['FoodSource']['dimensions'];
        
        $this->setScoutLimit($this->getColonySize() * $dimensions/2.0);

        self::log2file("Started simulation : ". $config['description']. " on " . (date("Y-m-d h:i:sa")));

    }

    /**
     * The main runner method. Runs the Bee Colony Algorithm
     *
     * @return $self
     */
    public function run()
    {
        $bestFoodSource = null;                 // Keeps track of Best nectar so far
        $maxCycles = $this->getMaxCycles();     // Parameters

        
        $this->initializeFoodSources();         // Setup random food sources
        
        $this->memorizeBestFoodSources();       // Memorize the best source
        //echo ("Food Sources \n");
        //print_r($this->foodSources);

        // Run max cycle cycles
        //self::log2file("Cycle,functionValue,nectar");
        //$prevNectar = 0;
        foreach (range(1, $maxCycles) as $cycle) {
            //print("Running Cycle $cycle\n");

            
            $this->sendEmployedBees();          // send in the employed bees for food
                                                // Employed bees return with nectar
            $this->updateProbabilities();       // Update disarability of food sources
                                                // The onlooker bees will now pick a food source
                                                // and explore the neighborhood, replacing with better
            $this->sendOnlookerBees();          // food sources when found

            $this->memorizeBestFoodSources();   // memorize the best food source for this cycle
            //$this->printLog();
                                                // Now time to replace food sources with a high 
            $this->sendScoutBees();             // trial count with new food sources found by scouts

            // For graphing
                    // graph unique food solutions
            foreach ($this->foodSources as $fs) {
                $fs->logData($cycle);
            }
            
        }
        
        self::log2file("Ran for $cycle cycle(s).");
        return $this;
    }

    function sendOnlookerBees(): void
    {
        // There are as many onlookers are there are food sources
        $onlookers = $this->getMaxFoodSources();

        //echo "In sendonlookerbees \n";
        //print_r($this->foodSources);

        foreach (range(0, $onlookers - 1) as $onlooker) {

            $r = 0.0 + (mt_rand() / mt_getrandmax()) * 1.0;
            $propSum = 0;
            foreach ($this->foodSources as $fsNum => $foodSource) {

                // Sum of propabilities so far
                //echo "In sendonlookerbees 2 \n";
                //print_r($foodSource);
                $propSum += $foodSource->getProbability();

                // if rand num is less than sum of prob
                if ($r <= $propSum) {
                    $newFoodSource = $this->getNewBasedOn($foodSource, $fsNum);
                    // then evaluates its nectar amount
                    // Is this new food source better than the old one

                    if ($foodSource->getNectar() < $newFoodSource->getNectar()) {
                        //YES! Replace the old one
                        array_splice($this->foodSources, $fsNum, 1, [$newFoodSource]);
                    } else {
                        //No. Increment the trial counter.
                        $foodSource->incrementTrialCount();
                    }
                    break;
                }
            }
        }
    }


    function sendScoutBees(): void
    {
        $scountLimit = $this->getScoutLimit();

        $maxTrialFs = $this->foodSources[0];
        $maxTrialFsNum = 0;
        foreach ($this->foodSources as $fsNum => $foodSource) {
            if ($foodSource->getTrialCount() > $maxTrialFs->getTrialCount()) {
                $maxTrialFs = $foodSource;
                $maxTrialFsNum = $fsNum;
            }
        }
        
        //echo "----- ". $maxTrialFs->getTrialCount()." > ". $scountLimit."\n";
        if ($maxTrialFs->getTrialCount() > $scountLimit) {
            $newFoodSource = $this->generateNewFoodSource();
            array_splice($this->foodSources, $maxTrialFsNum, 1, [$newFoodSource]);
        }
    }

    function updateProbabilities(): void
    {
        // Sum of nectar
        $sum = array_reduce($this->foodSources, function ($accumulator, $fs) {
            return $accumulator + $fs->getNectar();
        });

        //echo "prob sum is $sum\n";
        // Devide each
        foreach ($this->foodSources as $fs) {
            $fs->setProbability($fs->getNectar() / $sum);
        }
    }

    function sendEmployedBees(): void
    {
        // Each employed bee goes to a food source in her memory
        foreach ($this->foodSources as $fsNumber => $foodSource) {
            $newFoodSource = $this->getNewBasedOn($foodSource, $fsNumber);
            // then evaluates its nectar amount
            // Is this new food source better than the old one

            if ($foodSource->getNectar() < $newFoodSource->getNectar()) {
                //YES! Replace the old one
                array_splice($this->foodSources, $fsNumber, 1, [$newFoodSource]);
                //echo "After splice\n";
                //print_r($this->foodSources);

            } else {
                //No. Increment the trial counter.
                $foodSource->incrementTrialCount();
            }
        }
    }

    function getNewBasedOn($foodSource, $fsNumber): AbstractFoodSource
    {

        $fsCount = count($this->foodSources);
        // determines a source close buy,
        // The bee evaluates a position in the direction
        // of a neighboring bee to see if it yeilds
        // more nectar

        // Pick a neighbor (not itself)
        $neighborFsNumber = mt_rand(0, $fsCount - 2);
        // this eliminates picking itself
        if ($neighborFsNumber >= $fsNumber) {
            $neighborFsNumber += 1;
        }

        // echo "-- in getNewBasedOn\n";
        // print_r($foodSource);
        //echo "-- getNewBasedOn $neighborFsNumber --\n";
        //print_r($this->foodSources);

        // Get a new food source from another one
        $newFoodSource = $foodSource->getNeighoring($this->foodSources[$neighborFsNumber]);

        return $newFoodSource;
    }

    /**
     * Initialize Food Sources initializes the food sources with random locations
     *
     * @return $self
     */
    function initializeFoodSources()
    {
        $this->foodSources = array();

        // config  
        $maxFoodSources = $this->getMaxFoodSources();

        // Initialize up to max food sources
        foreach (range(0, $maxFoodSources - 1) as $foodSourceNum) {
            // Each with random parameters
            array_push($this->foodSources,  $this->generateNewFoodSource());
        }

        return $this;
    }

    /**
     * Returns the food source with the highest nectar in foodSources array
     *
     * @return FoodSource 
     */
    public function getHighestNectarSource()
    {
        //echo "\n\n---";
        //print_r($this->foodSources);
        $bestFoodSource = array_reduce($this->foodSources, function ($bestFoodSource, $fs) {
            if (empty($bestFoodSource) || $bestFoodSource->getNectar() < $fs->getNectar()) {
                $bestFoodSource = $fs;
            }
            return $bestFoodSource;
        });

        return $bestFoodSource;
    }

    
    function generateNewFoodSource() {
        $foodSourceConfig = $this->getFoodSourceConfig();
        $foodSourceClass = $foodSourceConfig['class'];

        require_once($foodSourceClass.".php");
        $class = new \ReflectionClass('\\ABCAlgorithm\\'.$foodSourceClass);
        return $class->newInstanceArgs([$foodSourceConfig]);
    }


    /**
     * Get the value of bestFoodSources
     */
    public function getBestFoodSources()
    {
        return $this->bestFoodSources;
    }

    /**
     * Adds to the value of bestFoodSources if better than what is there
     *
     * @return  self
     */
    public function memorizeBestFoodSources()
    {
        // Group the food sources by id
        $foodSourcesMap = array();
        foreach ($this->foodSources as $foodSource) {
            if (empty($foodSourcesMap[$foodSource->getIdentifier()])) {
                $foodSourcesMap[$foodSource->getIdentifier()] = $foodSource;
            } else {
                if ($foodSourcesMap[$foodSource->getIdentifier()]->getNectar() < $foodSource->getNectar() ) {
                    $foodSourcesMap[$foodSource->getIdentifier()] = $foodSource;
                }
            }
        }

        // Now we have all the food sources organized by ids. Sort them to get top numSolutions
        $uniqueFoodSources = array_values($foodSourcesMap);

        foreach (range(0, $this->numSolutions-1) as $iteration) {
            foreach (range(count($uniqueFoodSources)-1, 1) as $fsNum) {
                if ($uniqueFoodSources[$fsNum]->getNectar() > $uniqueFoodSources[$fsNum-1]->getNectar() ) {
                    // swap
                    $tempFs = $uniqueFoodSources[$fsNum-1];
                    $uniqueFoodSources[$fsNum-1] = $uniqueFoodSources[$fsNum];
                    $uniqueFoodSources[$fsNum] = $tempFs;
                }
            }
        }
        // Deduping. This is extreme. Must subclass.
        
        //$this->foodSources = $uniqueFoodSources;
        // Now the top numsolutions of the array are sorted. Pick those off.
        $this->bestFoodSources = array_slice($uniqueFoodSources, 0, $this->numSolutions);
        foreach ($this->bestFoodSources as $fs) {
            $fs->resetTrialCount();
        }

        return $this;
    }

    /**
     * Get the value of foodSources
     */
    public function getFoodSources()
    {
        return $this->foodSources;
    }


    /**
     * Get the value of maxCycles
     */ 
    public function getMaxCycles()
    {
        return $this->maxCycles;
    }

    /**
     * Set the value of maxCycles
     *
     * @return  self
     */ 
    protected function setMaxCycles($maxCycles)
    {
        $this->maxCycles = $maxCycles;

        return $this;
    }


    /**
     * Get the value of colonySize
     */ 
    public function getColonySize()
    {
        return $this->colonySize;
    }

    /**
     * Set the value of colonySize
     *
     * @return  self
     */ 
    protected function setColonySize($colonySize)
    {
        $this->colonySize = $colonySize;
        
        // Food Sources
        $this->setMaxFoodSources ($colonySize / 2);

        // Limit for scout
       // $this->setScoutLimit($colonySize * $this->getDimensions() / 2);
        return $this;
    }

        /**
     * Get the value of maxFoodSources
     */ 
    public function getMaxFoodSources()
    {
        return $this->maxFoodSources;
    }

    /**
     * Set the value of maxFoodSources
     *
     * @return  self
     */ 
    protected function setMaxFoodSources(int $maxFoodSources)
    {
        $this->maxFoodSources = $maxFoodSources;

        return $this;
    }

    /**
     * Get the value of scoutLimit
     */ 
    public function getScoutLimit()
    {
        return $this->scoutLimit;
    }

    /**
     * Set the value of scoutLimit
     *
     * @return  self
     */ 
    protected function setScoutLimit(int $scoutLimit)
    {
        $this->scoutLimit = $scoutLimit;

        return $this;
    }

    /**
     * Get the value of numSolutions
     */ 
    public function getNumSolutions()
    {
        return $this->numSolutions;
    }

    /**
     * Set the value of numSolutions
     *
     * @return  self
     */ 
    public function setNumSolutions(int $numSolutions)
    {
        $this->numSolutions = $numSolutions;

        return $this;
    }

    /**
     * Get the value of beeConfig
     */ 
    public function getFoodSourceConfig()
    {
        return $this->beeConfig;
    }

    /**
     * Set the value of beeConfig
     *
     * @return  self
     */ 
    protected function setFoodSourceConfig(array $beeConfig)
    {
        $this->beeConfig = $beeConfig;

        return $this;
    }
    
    protected function PrintLog() {
        $message = "";

        foreach ($this->bestFoodSources as $fs) {
            $message .= $fs->getPrintableCSV().",";
        }
        $message = substr($message, 0, -1);
        self::log2file($message);
    }

    static function log2file($message) {
        if (empty(self::$logFile)) {
            self::$logFile = fopen("bee-colony-log.txt", "w");
        }

        fwrite(self::$logFile, $message."\n");
    }
}
