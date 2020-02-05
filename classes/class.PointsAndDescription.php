<?php

/**
 * Class PointsAndDescription
 *
 * Class which is returned from an evaluation calculation which stores points and a description.
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 */
class PointsAndDescription
{
    public function __construct($points, $description)
    {
        $this->points = $points;
        $this->description = $description;
    }

    public function getPoints()
    {
        return $this->points;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
}
