<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;


/**
 * A class for rendering a heatmap visualization.
 * 
 * This class generates and displays a heatmap based on provided data.
 * @since 2.2.6
 */
final class Heatmap
{


    private $data = array();
    private $dowmap = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');


    /**
     * Constructor for initializing the Heatmap with data.
     *
     * @param array $data An array containing heatmap data.
     */
    function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Render the heatmap.
     * 
     * This function generates and displays the heatmap visualization based on the provided data.
     *
     * @since 2.2.6
     */
    function render()
    {
        // Determine the maximum value for color scaling
        $max = 10;
        if (!empty($this->data)) {
            $max = max(array_map(function ($item) {
                return intval($item["total_hits"]);
            }, $this->data));
        }

        if ($max < 10) {
            $max = 10;
        }

        echo "<div class=\"gdcp-heatmap-container\">";

        // Generate rows for each day of the week
        foreach (range(0, 6) as $weekday) {

            echo "<div class=\"gdcp-heatmap-row\">";

            // Display the day of the week abbreviation
            if ($weekday % 2 != 0) {
                echo "<span class=\"dow\">{$this->dowmap[$weekday]}</span>";
            } else {
                echo "<span class=\"dow\">&nbsp;</span>";
            }

            // Generate cells for each hour of the day
            foreach (range(0, 24) as $hour) {

                $data = array_values(array_filter($this->data, function ($dt) use ($weekday, $hour) {
                    return $dt['weekday'] == $weekday && $dt['hour'] == $hour;
                }));

                if (!empty($data)) {

                    $hits = intval($data[0]["total_hits"]);
                    $plural = $hits > 1;

                    $color = self::get_color($hits, $max);

                    $time = date_i18n("M j, H:i", strtotime($data[0]["time"]));

                    // Display heatmap cell with hit information
                    echo "<div class=\"gdcp-heatmap-cell\" style=\"background-color: $color;\">";
                    echo "<p class=\"info\"><strong>$hits " . ($plural ? "hits - " : "hit - ") . "</strong><span>$time</span><i class=\"arrow-down\"></i></p>";
                    echo "</div>";
                } else {
                    echo "<div class='gdcp-heatmap-cell'></div>";
                }
            }

            echo "</div>";
        }

        echo "</div>";
    }

    /**
     * Generate a visual representation of the heatmap color scale.
     *
     * @return string A string containing HTML markup for the color scale.
     * @since 2.2.6
     */
    static function map()
    {
        $result = "<span class=\"gdcp-chart-colors\">";

        foreach (range(0, 9) as $i) {
            $color = self::get_color($i, 9);
            $result .= "<i class=\"gradient\" style=\"background-color: $color;\"></i>";
        }

        $result .= "</span>";

        return $result;
    }

    /**
     * Generate a color based on the value and maximum value for color scaling.
     *
     * @param int $value The value for which to generate a color.
     * @param int $max The maximum value for color scaling.
     * @return string A color in HSL format.
     * @since 2.2.6
     */
    static function get_color($value, $max)
    {
        $h = (1.0 - ($value / $max)) * 240;
        return "hsl(" . $h . ", 100%, 50%)";
    }
}
