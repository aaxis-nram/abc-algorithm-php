# Artificial Bee Colony Algorithm

Introduced by Karaboga in 2005, the Artificial Bee Colony (ABC) algorithm is a swarm based meta-heuristic algorithm for optimizing numerical problems. It was inspired by the intelligent foraging behavior of honey bees. 

To install, run the following:
<pre>
$ composer update
</pre>

To run the algorithm:

<pre>
$ php ./ABCRunner.php config.ini

E.g.,
$ php ./ABCRunner.php configSphere2D.ini
</pre>

The example command will generate a log file in out folder.

##  Classes

### BeeColony
Is the main runner class. Leverages the food source class

### AbstractFoodSource
Base for all food sources. You should extend this one or the SphereFoodSource for simple numerical problems

### SphereFoodSource
Conrete implementation of AbstractFoodSource for Sphere food source.

### HimmelblausFoodSource
Concrete implementation of AbstractFoodSource for Himmelblau function

### CrossInTrayFoodSource
Concrete implementation of CrossInTraySource for CrossInTray function

### MatchPlayFoodSource
Solves the MatchPlay Puzzle.

# Visualisation
Run

<pre>
php ./NumericalSolnVisualizer.php configCrossInTray.ini  
gifsicle --loopcount=forever --delay=25 out/crossintray_* --colors 256 > final/crossintray-soln.gif
"c:\Program Files\ImageMagick-7.0.11-Q16-HDRI\magick.exe" "convert"  "-delay" 25 "-loop" 0 "out\sphere-2d_*.gif" "sphere-anim.gif"
</pre>