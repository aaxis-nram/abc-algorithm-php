# Base Graph Generation

Contour Plot at 
https://academo.org/demos/contour-plot/
Maginification 125

3D Surface Plotter
https://academo.org/demos/3d-surface-plotter/
100% magnification


## Sphere

Expression:
    (x-0.5)*(x-0.5) + (y-0.5)*(y-0.5)

Range of Graph:
-5,5

Range of contour levels:
-100 100

Number of contours
25

## Himmelblaus

Expression:
pow((pow(x,2) + y -11), 2) + pow((x + pow(y,2) -7), 2)

For Contour
(x*x + y - 11)*(x*x + y - 11) + (x + y*y - 7)*(x + y*y - 7)

Range of Graph:
-10,10

Range of contour levels:
-600 600

Number of contours
25

## CrossInTray

Expression
-0.0001 * pow( (abs( sin(x)*sin(y)* pow(2.7182818, abs(100 - sqrt(pow(x,2) + pow(y,2))/3.1415 ))) + 1), 0.1)

-10,10
-2,-0.2

## Restrigin

Expression
10*2 + (  Math.pow(x,2) - 10*cos( 2.0 * 3.1415 * x )  )  + (  Math.pow(y,2) - 10*cos( 2.0 * 3.1415 * y )  )

-5,5
0,60


742,742
to 762 762
Then to 762, 862

# Generate GIF

"c:\Program Files\ImageMagick-7.0.11-Q16-HDRI\magick.exe" convert  -delay 25 -loop 0 out\sphere-2d_*.gif sphere-anim.gif  
