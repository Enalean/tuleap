<?php

class Graph {

	var $xtitle;
	var $ytitle;
	var $im;
	var $color;
	var $xmin;
	var $xmax;
	var $ymin;
	var $ymax;
	var $xdata_diff;
	var $ydata_diff;
	var $graph_height;
	var $graph_width;
	var $xpad;
	var $ypad;
	var $image_height;
	var $image_width;
	var $data_set;
	var $num_data_sets;
	var $num_points;
	var $strDebug;


	/**
	 * The function constructor sets up the basic vars needed to draw a graph.  
	 * It sets up the geometry for the graph
	 * as well as any data extents that need to be set.
	 */
	function __construct( $width = 640, $height = 480 ) {

		$this->xpad = 50;
		$this->ypad = 40;
		
		$this->graph_height = $height - (2 * $this->ypad);
		$this->graph_width  = $width  - (2 * $this->xpad);
		
		$this->image_height = $height;
		$this->image_width  = $width;

		$this->im    = ImageCreate($this->image_width, $this->image_height);
		$this->color = array();

		$this->color['white']		= ImageColorAllocate($this->im,255,255,255);
		$this->color['black']		= ImageColorAllocate($this->im,0,0,0);
		$this->color['red']		= ImageColorAllocate($this->im,180,0,0);
		$this->color['darkred']		= ImageColorAllocate($this->im,120,0,0);
		$this->color['green']		= ImageColorAllocate($this->im,0,180,0);
		$this->color['darkgreen']	= ImageColorAllocate($this->im,0,120,0);
		$this->color['blue']		= ImageColorAllocate($this->im,0,0,180);
		$this->color['darkblue']	= ImageColorAllocate($this->im,0,0,120);
		$this->color['gray']		= ImageColorAllocate($this->im,180,180,180);
		$this->color['darkgray']	= ImageColorAllocate($this->im,64,64,64);
		$this->color['magenta']		= ImageColorAllocate($this->im,240,0,240);
		$this->color['darkmagenta']	= ImageColorAllocate($this->im,180,0,180);

	} // function Graph Constructor


	/**
	 * SetPad redefines the x and y padding distances on the object.
	 *
	 * @param $xpad the x distance on either side reserved for markings. 
	 * @param $ypad the y distance on the top and bottom reserved for markings.
	 */
	function SetPads( $xpad = 50, $ypad = 40 ) {
		$this->xpad = $xpad;
		$this->ypad = $ypad;

		$this->graph_height = $this->image_height - (2 * $this->ypad);
		$this->graph_width  = $this->image_width  - (2 * $this->xpad);
	}

	/**
	 * AddData adds an array of prefetched data to this object.
	 *
	 * @param $xdata The x data to add
	 * @param $ydata The y data to add
	 */
	function AddData( $xdata, $ydata, $xlabel = 0 ) {
		$this->num_data_sets++;
		//asort( $xdata );
		$i = 0;

		$this->strDebug[] = "Adding dataset " . $this->num_data_sets . " " 
					. ($xlabel ? "with a label" : "without a label") . " to the datasets.";

		while (list($index,$val) = each($xdata)) {
			$this->data_set[$this->num_data_sets]['x'][$i] = $xdata[$index];
			$this->data_set[$this->num_data_sets]['y'][$i] = $ydata[$index];

			if ( $xlabel ) {
				$this->data_set[$this->num_data_sets]['xlabel'][$i] = $xlabel[$index];
			} else {
				$this->data_set[$this->num_data_sets]['xlabel'][$i] = $xdata[$index];
			}
			++$i;
		}

		$this->num_points[$this->num_data_sets] = min(sizeof($xdata),sizeof($ydata));

		if ($this->num_data_sets == 1) {
			$this->xmax = max($xdata);
			$this->xmin = min($xdata);
			$this->ymax = max($ydata);
			$this->ymin = (min($ydata) < 0) ? min($ydata) : 0;
		} else {
			$tmp_xmax = max($xdata);
			$tmp_xmin = min($xdata);
			$tmp_ymax = max($ydata);
			$tmp_ymin = min($ydata);

			$this->xmax = max($this->xmax,$tmp_xmax);
			$this->xmin = min($this->xmin,$tmp_xmin);
			$this->ymax = max($this->ymax,$tmp_ymax);
			$this->ymin = (min($this->ymin,$tmp_ymin) < 0 ) ? min($this->ymin,$tmp_ymin) : 0;
		} 

		$this->xdata_diff = (($this->xmax) - ($this->xmin));
		$this->ydata_diff = (($this->ymax) - ($this->ymin));

		return $this->num_data_sets;

	} // function AddData


	/**
	 * translate shifts the $x and $y arguments from points in the world space to
	 * pixels in graph plane space.
	 *
	 * @param &$x The x position in world coordinates
	 * @param &$y The y position in world coordinates
	 * @param &$xpos The x position in screen pixels
	 * @param &$ypos The y position in screen pixels
	 */
	function translate( &$x, &$y, &$xpos, &$ypos ) {
		$xpos = $this->xdata_diff ? ($this->graph_width / $this->xdata_diff) * ($x - $this->xmin) + $this->xpad : 0 + $this->xpad;
		$ypos = $this->ydata_diff ? ((($this->ymax - $y) / $this->ydata_diff) * $this->graph_height) + $this->ypad : 0 + $this->ypad;
	} 


	/**
	 * adjustNum makes a number better for axis spacing.
	 * Instead of having something like .12452314 it should be closer to .12 for presentability
	 *
	 * @param $num This is the number you want to adjust
	 * @param $data_diff This is the total range of values that are spanned by this axis
	 * @param $num_divisions How many divisions will there be?
	 */
	function adjustNum ( $num, $data_diff, $num_divisions ) {

		$data_diff = abs($data_diff);
		$adjusted  = $data_diff / $num_divisions;

		if ( $num == 0 ) {

			return $num;

		} elseif ($adjusted >= 1) {

			$decimals = strlen(floor($adjusted)."") - 1;
			$divisor  = pow(10,$decimals);
			$num      = floor($num / $divisor) * $divisor;

		} else {

			list($zero,$adjusted) = explode(".",$adjusted);
			$decimals = strlen(floor(1/$num) . "") + 1;
			$divisor  = pow(10,$decimals);
			$num      = round($num * $divisor) / $divisor;

		} 

		return $num;

	} // function adjustNum


	/**
	 * DrawLine draws a line with point value inputs.
	 * This translates the input coordinates into screen space and draws a line
	 *
	 * @param $x1 The first point's x coordinate (in the world coordinate view)
	 * @param $y1 The first point's y coordinate (in the world coordinate view)
	 * @param $x2 The second point's x coordinate (in the world coordinate view)
	 * @param $y2 The second point's y coordinate (in the world coordinate view)
	 * @param $color The color to draw the line
	 */
	function DrawLine ( $x1, $y1, $x2, $y2, $color ) {

		$this->translate( $x1, $y1, $x1pos, $y1pos );
		$this->translate( $x2, $y2, $x2pos, $y2pos );

		ImageLine( $this->im, $x1pos, $y1pos, $x2pos, $y2pos, $color );
	} 




	/**
	 * DrawFilledPolygon is a wrapper to the imageFilledPolygon GD function.
	 *
	 * @param $verts The vertices for the polygon
	 * @param $color The color you want the polygon to be
	 */
	function DrawFilledPolygon ( $verts, $color ) {

		for ( $i = 0; $i < sizeof($verts); $i++ ) {
			$this->translate( $verts[$i], $verts[$i+1], $verts[$i], $verts[++$i] );
		}

		imageFilledPolygon( $this->im, $verts, (sizeof($verts) / 2), $color );
	} 


	/**
	 * DrawShadowedPolygon is a wrapper to the imageFilledPolygon GD function that 
	 * first creates a drop shadow.
	 *
	 * @param $verts The vertices for the polygon
	 * @param $color The color you want the polygon to be
	 */
	function DrawShadowedPolygon ( $verts, $color ) {

		for ( $i = 0; $i < sizeof($verts); $i++ ) {
			$this->translate( $verts[$i], $verts[$i+1], $verts[$i], $verts[++$i] );
		}

		imageFilledPolygon( $this->im, $verts, (sizeof($verts) / 2), $color );
	} 


	/**
	 * DrawDashedLine draws a dashed line from the start coordinate to the end coordinate
	 *
	 * @param $x1 The first point's x coordinate (in the world coordinate view)
	 * @param $y1 The first point's y coordinate (in the world coordinate view)
	 * @param $x2 The second point's x coordinate (in the world coordinate view)
	 * @param $y2 The second point's y coordinate (in the world coordinate view)
	 * @param $dash_length The length of a dash on the dashed line
	 * @param $dash_space The length of a space in the dashed line
	 * @param $color
	 */
	function DrawDashedLine ($x1,$y1,$x2,$y2,$dash_length,$dash_space,$color) {
	
		$this->translate($x1,$y1,$x1pos,$y1pos);
		$this->translate($x2,$y2,$x2pos,$y2pos);

		// Get the length of the line in pixels
		$line_length = ceil( sqrt( pow(($x2pos - $x1pos),2) + pow(($y2pos - $y1pos),2) ) );

		$cosTheta = $line_length ? ($x2pos - $x1pos) / $line_length : 0;
		$sinTheta = $line_length ? ($y2pos - $y1pos) / $line_length : 0;
		$lastx    = $x1pos;
		$lasty    = $y1pos;

		   // Let's draw the dashed line
		   // for as we go along the length of the line
		for ( $i = 0; $i < $line_length; $i += ($dash_length + $dash_space) ) {
			$xpos = ($dash_length * $cosTheta) + $lastx;
			$ypos = ($dash_length * $sinTheta) + $lasty;
			
			ImageLine( $this->im, $lastx, $lasty, $xpos, $ypos, $color );
			$lastx = $xpos + ($dash_space * $cosTheta);
			$lasty = $ypos + ($dash_space * $sinTheta);
		} 
	}

	/**
	 * DrawGrid draws the grid lines for the graph
	 *
	 * @param $color The color to draw the grid lines in. 
	 */
	function DrawGrid( $color ) {
		
		$color    = $this->color[$color];
		$numGrid  = 10;
		$xNum     = $this->graph_width  / 30;
		$yNum     = $this->graph_height / 30;


		   // If we have a NULL data set, assume some sane defaults. 
		if ( $this->ydata_diff == 0 ) {
			$this->ydata_diff = 10;
			$this->ymax = $this->ymin + 10;
		}

		$numxGrid = min($numGrid, $xNum);
		$xTick    = $this->adjustNum( ($this->xdata_diff / $numxGrid), $this->xdata_diff, $numxGrid );
		$xStart = floor($this->xmin / $xTick);
		$xEnd   = ceil(($this->xmax ? $this->xmax : 0) / $xTick);

		$numyGrid = min($numGrid, $yNum);
		$yTick    = $this->adjustNum( ($this->ydata_diff / $numyGrid), $this->ydata_diff, $numyGrid );
		$yStart = floor($this->ymin / $yTick);
		$yEnd   = ceil(($this->ymax ? $this->ymax : 0) / $yTick);

		$this->strDebug[] = "";
		$this->strDebug[] = "xNum = $xNum  numxGrid = $numxGrid  xTick = $xTick xStart = $xStart xEnd = $xEnd ";
		$this->strDebug[] = "yNum = $yNum  numyGrid = $numyGrid  yTick = $yTick yStart = $yStart yEnd = $yEnd ";
		$this->strDebug[] = "xdata_diff = $this->xdata_diff  ydata_diff = $this->ydata_diff";

		  // make sure that our scale always fits nicely.
		$this->ymin = ( $yStart * $yTick );
		$this->ymax = ( $yEnd * $yTick );
		$this->ydata_diff = ( $this->ymax - $this->ymin );

		   // Draw the vertical grid lines
		for ( $gridCount = $xStart; $gridCount <= $xEnd; $gridCount++ ) {	
			$gridx = $gridCount * $xTick;

			$this->DrawDashedLine($gridx, $this->ymin, $gridx, $this->ymax, 2, 3, $color);

			   // world $gridx,$ymin  -> graph $x0 $y0
			$this->translate($gridx,$this->ymin,$x0,$y0);
			ImageLine  ($this->im,$x0,$y0 + 3,$x0,$y0 - 3,$this->color['black']);
			$gridx = $this->data_set[1]['xlabel'][$gridx];
			ImageString($this->im,1,$x0 - 2.5 * strlen($gridx),$y0 + 6,$gridx,$this->color['black']);
		} 

		   // Draw the horizontal grid lines
		for ( $gridCount = $yStart; $gridCount <= $yEnd; $gridCount++ ) {
			$gridy = $gridCount * $yTick;

			if ( $gridy == 0 ) {
				$this->DrawLine( $this->xmin, $gridy, $this->xmax, $gridy, $color );
			} else {
				$this->DrawDashedLine( $this->xmin, $gridy, $this->xmax, $gridy, 2, 3, $color );
			}

			$this->translate($this->xmin,$gridy,$x0,$y0);
			ImageLine( $this->im, $x0 - 3, $y0, $x0 + 3, $y0, $this->color['black'] );
			ImageString( $this->im, 1, $x0 - 5 * strlen($gridy) - 3, $y0 - 4, $gridy, $this->color['black'] );
		} 
	} // function DrawGrid

	
	/**
	 * DrawAxis draws the x-axis and the y-axis for the graph
	 */
	function DrawAxis() {
		$this->DrawLine($this->xmin,$this->ymin,$this->xmax,$this->ymin,$this->color['black']);
		$this->DrawLine($this->xmin,$this->ymin,$this->xmin,$this->ymax,$this->color['black']);
	} // function DrawAxis


	/**
	 * LineGraph draws a line graph from the data set that gets passed in.
	 * This takes in 2 arrays and loops until the end of the smallest one.
	 *
	 */
	function LineGraph ($dataset,$color) {

		$color = $this->color[$color];
		
		$lastx = $this->data_set[$dataset]['x'][0];
		$lasty = $this->data_set[$dataset]['y'][0];
		
		for ($i = 1; $i < $this->num_points[$dataset]; ++$i) {
			$this->DrawLine($lastx, $lasty, $this->data_set[$dataset]['x'][$i], $this->data_set[$dataset]['y'][$i], $color);
			$lastx = $this->data_set[$dataset]['x'][$i];
			$lasty = $this->data_set[$dataset]['y'][$i];
		} 

	} // function LineGraph


	/**
	 * FilledLineGraph draws a filled line graph for the data.
	 *
	 * @param $xdata The array of x-data.
	 * @param $ydata The array of y-data.
	 * @param $color The color you want the graph drawn.
	 */
	function FilledLineGraph( $dataset, $color, $colortwo = 0 ) {
		
		$color = $this->color[$color];
		$lastx = $this->data_set[$dataset]['x'][0];
		$lasty = $this->data_set[$dataset]['y'][0];

		// $this->strDebug[] = "lastx: $lastx, lasty: $lasty";

		for ($i = 1; $i < $this->num_points[$dataset]; ++$i) {
			$verts[0] = $lastx;
			$verts[1] = $lasty;
			$verts[2] = $this->data_set[$dataset]['x'][$i];
			$verts[3] = $this->data_set[$dataset]['y'][$i];
			$verts[4] = $this->data_set[$dataset]['x'][$i];
			$verts[5] = $this->ymin;
			$verts[6] = $lastx;
			$verts[7] = $this->ymin;

			$lastx = $this->data_set[$dataset]['x'][$i];
			$lasty = $this->data_set[$dataset]['y'][$i];

			if ( $colortwo ) {
				$this->DrawShadowedPolygon( $verts, $colortwo );
			} else {
				$this->DrawFilledPolygon( $verts, $color );
			}
		} 

	} // function FilledLineGraph


	/**
	 * addDebug allows the appending of debug information to the graph from the calling script
	 */
	function addDebug( $message ) {
		$this->strDebug[] = $message;
	}


	/**
	 * showDebug shows debugging text on the graph in case you want to show data on the graph that never usually gets output.
	 */
	function showDebug() {
		$lines = 0;

		while ( list($key,$str) = each($this->strDebug) ) {
			$lpad = $span = 0;
			for ( $i = 0; $i < strlen($str); $i += $span )  {
				$span = ($this->image_width - (($this->xpad * 2) + 5 + ($lpad ? 20 : 0))) / 5;
				ImageString( $this->im, 1,
					$this->xpad + 5 + ($lpad++ ? 20 : 0), 
					$this->ypad + 5 + ($lines++ * 13),
					substr( $str, $i, $span ), 
					$this->color['red'] );
			}
		}

	} 


	/**
	 * SetTitle draws a title on the graph.
	 *
	 * @param $title The title of the graph.
	 */
	function SetTitle($title) {

		$text_left = ($this->image_width / 2) - (strlen($title) * 2.7);
		ImageString($this->im,2,$text_left,5,$title,$this->color['black']);

	} 


	/**
	 * SetSubTitle draws a sub title on the graph in smaller text below the main title.
	 *
	 * @param $subtitle The title of the graph.
	 */
	function SetSubTitle($subtitle) {
		$text_left = ($this->image_width / 2) - (strlen($subtitle) * 2.4);
		ImageString($this->im,1,$text_left,25,$subtitle,$this->color['black']);
	} 


	/**
	 * SetxTitle sets a title below the x-axis.
	 */
	function SetxTitle($xtitle) {
		$text_left = ($this->image_width / 2) - (strlen($xtitle) * 2.4);
		ImageString($this->im,1,$text_left,($this->image_height - $this->ypad + 20),$xtitle,$this->color['black']);
	} 


	/**
	 * SetyTitle sets a title to the left of the y-axis.
	 */
	function SetyTitle($ytitle) {
		$text_left = 10;
		$text_top  = ($this->image_height / 2) + (strlen($ytitle) * 2.4);
		ImageStringUp($this->im,1,$text_left,$text_top,$ytitle,$this->color['black']);
	} 


	/**
	 * ShowGraph sets the header type and displays the graph.
	 */
	function ShowGraph( $type = "png" ) {

		if ( $type == "gif" ) {

			header("Content-Type:image/gif");
			ImageGIF($this->im);

		} elseif ( $type == "jpeg" ) {

			header("Content-Type:image/jpeg");
			ImageJPEG($this->im);

		} else {

			header("Content-Type:image/png");
			ImagePNG($this->im);
		}

		ImageDestroy($this->im);
	}

} // class Graph


//
//  EXAMPLE CODE;
//
/*
for ($i = 0; $i <= 100; ++$i) {
	$x[] = $i;
	$xlabel[] = "Value $i";
	$y[] = (pow($i,3) - (170 * sin($i / 15)) * pow($i,2) - 5 * $i + 40000) / 43247823 ;
	$y2[] = (sin($i/10) / 100) - .007;
}

$graph = new Graph;
$graph->InitGraph(500,500);

$data1 = $graph->AddData($x,$y, $xlabel );
$data2 = $graph->AddData($x,$y2, $xlabel );

$graph->SetTitle('Sweet Ass Graphs');
$graph->SetSubTitle('A selection of mathematical functiond for your pleasure.');
$graph->SetxTitle('Counted data');
$graph->SetyTitle('Some foo data');
$graph->DrawGrid('gray');

$graph->FilledLineGraph($data1,'red');
$graph->LineGraph($data2,'magenta');

$graph->DrawAxis();
$graph->showDebug();
$graph->ShowGraph();

*/

?>
