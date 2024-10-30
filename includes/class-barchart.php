<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;


/**
 * Barchart Class
 *
 * This class generates a simple bar chart SVG representation based on provided data and dimensions.
 * 
 * @since 2.4.5
 */
final class Barchart
{

    private $data = array();
    private $width = 0;
    private $height = 0;
    private $gap = 1;


    /**
     * Constructor
     *
     * @param array $data   Data for the chart.
     * @param int   $w      Width of the chart.
     * @param int   $h      Height of the chart.
     * @param int   $g      Gap between bars.
     */
    function __construct($data, $w, $h, $g = 1)
    {
        $this->data = $data;
        $this->width = $w;
        $this->height = $h;
        $this->gap = $g;
    }


    /**
     * Render the Barchart
     *
     * Generates and outputs an SVG representation of the barchart.
     *
     * @since 2.4.5
     */
    function render()
    {

        // Find the maximum value in the data to calculate the yScale
        $max = 0;
        if (!empty($this->data)) {
            $max = max(array_map(function ($item) {
                return intval($item["value"]);
            }, $this->data));
        }


        // Calculate the yScale to determine the scaling of the bars
        $yScale = ($this->height > 0) ? $max / $this->height : 0;

        // Calculate the width of each bar
        $barWidth = ($this->width / count($this->data)) - $this->gap;

        // Calculate the total height of the SVG including margins
        $svgHeight = $this->height + 25;


        // Start generating the SVG
        echo "<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" " .
            "xmlns:xlink=\"http://www.w3.org/1999/xlink\" " .
            "class=\"chart\" width=\"$this->width\" height=\"$svgHeight\" " .
            "aria-labelledby=\"title\" role=\"img\">";


        // Generate horizontal grid lines
        foreach ([0, $this->height / 2, $this->height] as $y) {
            echo "<g>" .
                "<line x1=\"0\" y1=\"$y\" x2=\"$this->width\" y2=\"$y\" stroke=\"#eee\" />" .
                "</g>";
        }


        // Generate bars for each data point
        foreach ($this->data as $index => $d) {

            // Extract the value and tooltip from the data
            $num = intval(isset($d['value']) ? $d['value'] : 0);
            $tooltip = isset($d['index']) ? $d['index'] : '';

            // Calculate the height of the bar based on the yScale
            $height = ($yScale > 0) ? $num / $yScale : $num;

            // Calculate the x and y coordinates for the bar and related elements
            $x = ($index * ($barWidth + $this->gap));
            $y = $this->height - $height;
            $lineX = ($barWidth / 2) + $x;
            $tooltipY = $this->height + 12;
            $tooltipX = min([max([$lineX, 9]), $this->width - 9]);

            // Generate tooltip markup if a tooltip is available
            $tooltipMarkup = '';
            if (!empty($tooltip)) {
                $tooltipMarkup = "<g class=\"tooltip\" style=\"transform:translate({$tooltipX}px,{$tooltipY}px);\">" .
                    "<circle class=\"bg\" r=\"9\" fill=\"#333\" />" .
                    "<text dy=\".35em\" x=\"0\" y=\"0\" text-anchor=\"middle\" class=\"count\" fill=\"#fff\">$tooltip</text>" .
                    "</g>";
            }

            // Output SVG elements for the bar and related components
            echo "<g class=\"bar\">" .
                "<line class=\"grid\" x1=\"$lineX\" y1=\"0\" x2=\"$lineX\" y2=\"$this->height\" stroke-width=\"4\" />" .
                "<rect y=\"$y\" x=\"$x\" width=\"$barWidth\" height=\"$height\" />" .
                $tooltipMarkup .
                "</g>";
        }

        // Close the SVG element
        echo "</svg>";
    }
}
