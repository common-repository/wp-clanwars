<?php
/*
    WP-Clanwars
    (c) 2011 Andrej Mihajlov

    This file is part of WP-Clanwars.

    WP-Clanwars is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WP-Clanwars is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WP-Clanwars.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WP_Clanwars;

class Utils {

    static function create_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0C2f ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0x2Aff ), mt_rand( 0, 0xffD3 ), mt_rand( 0, 0xff4B )
        );
    }

    static function get_wpmu_sites() {
        global $wpdb;
        if(function_exists('get_sites')) { // WP 4.6+
            return get_sites();
        } else {
            return $wpdb->get_results("SELECT * FROM $wpdb->blogs");
        }
    }

    static function get_list_table_action() {
        if(isset($_REQUEST['action']) && (int)$_REQUEST['action'] !== -1) {
            return $_REQUEST['action'];
        }

        if(isset($_REQUEST['action2']) && (int)$_REQUEST['action2'] !== -1) {
            return $_REQUEST['action2'];
        }

        return false;
    }

    /**
     * Parse arguments and restrict a list of values to keys defined in defaults
     *
     * @param array|string $args Input values
     * @param array $defaults Array of default values
     * @return array Merged array. Same behaviour as wp_parse_args except it generates array which only consists of keys from $defaults array
     */
    static function extract_args($args, $defaults) {
        $options = wp_parse_args($args, $defaults);
        $result = array();

        if(is_array($defaults)) {
            foreach(array_keys($defaults) as $key) {
                $result[$key] = $options[$key];
            }
        }

        return $result;
    }

    /**
     * Detect whether POST request was sent to server.
     * @return boolean true if POST request, otherwise false.
     */
    static function is_post() {
        return 'POST' == $_SERVER['REQUEST_METHOD'];
    }

    static function current_time_fixed( $type, $gmt = 0 ) {
        $t = ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) );
        switch ( $type ) {
            case 'mysql':
                return $t;
                break;
            case 'timestamp':
                return strtotime($t);
                break;
        }
    }

    static function all_countries() {
        static $countries = null;
        if($countries === null) {
            @include( realpath(dirname(__FILE__) . '/../countries.php') );
        }
        return $countries;
    }

    static function html_date_helper( $prefix, $time = 0, $tab_index = 0, $select_class = '' )
    {
        global $wp_locale;

        $tab_index_attribute = '';
        $tab_index = (int)$tab_index;
        if( $tab_index > 0 ) {
            $tab_index_attribute = " tabindex=\"$tab_index\"";
        }

        $select_class_attribute = '';
        if( !empty($select_class) ) {
            $select_class_attribute = ' class="' . esc_attr($select_class) . '"';
        }

        if($time == 0) {
            $time_adj = \WP_Clanwars\Utils::current_time_fixed('timestamp', 0);
        }
        else {
            $time_adj = $time;
        }

        $jj = date( 'd', $time_adj );
        $mm = date( 'm', $time_adj );
        $hh = date( 'H', $time_adj );
        $mn = date( 'i', $time_adj );
        $yy = date( 'Y', $time_adj );

        $month = "<select name=\"{$prefix}[mm]\"$select_class_attribute$tab_index_attribute>\n";
        for ( $i = 1; $i < 13; $i = $i +1 ) {
                $month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
                if ( $i == $mm )
                        $month .= ' selected="selected"';
                $month .= '>' . $wp_locale->get_month( $i ) . "</option>\n";
        }
        $month .= '</select>';

        $day = '<input type="text" name="'.$prefix.'[jj]" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off"  />';
        $hour = '<input type="text" name="'.$prefix.'[hh]" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off"  />';
        $minute = '<input type="text" name="'.$prefix.'[mn]" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off"  />';
        $year = '<input type="text" name="'.$prefix.'[yy]" value="' . $yy . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off"  />';

        printf(before_last_bar(__('%1$s%5$s %2$s @ %3$s : %4$s|1: month input, 2: day input, 3: hour input, 4: minute input, 5: year input', WP_CLANWARS_TEXTDOMAIN)), $month, $day, $hour, $minute, $year);
    }

    static function html_country_select_helper($p = array(), $print = true)
    {
        $all_countries = self::all_countries();
        extract(\WP_Clanwars\Utils::extract_args($p, array(
            'select' => '',
            'name' => '',
            'id' => '',
            'class' => '',
            'show_popular' => false
        )));

        ob_start();

        $attrs = array();

        if(!empty($id))
            $attrs[] = 'id="' . esc_attr($id) . '"';

        if(!empty($name))
            $attrs[] = 'name="' . esc_attr($name) . '"';

        if(!empty($class))
            $attrs[] = 'class="' . esc_attr($class) . '"';

        $attrstr = implode(' ', $attrs);
        if(!empty($attrstr)) $attrstr = ' ' . $attrstr;

        echo '<select' . $attrstr . '>';

        if($show_popular) {
            $popular = \WP_Clanwars\Teams::most_popular_countries();

            if(!empty($popular)) {
                foreach($popular as $i => $data) :
                    $abbr = $data['country'];
                    $title = isset($all_countries[$abbr]) ? $all_countries[$abbr] : $abbr;

                    echo '<option value="' . esc_attr($abbr) . '">' . esc_html($title) . '</option>';
                endforeach;
                echo '<optgroup label="-----------------" style="font-family: monospace;"></optgroup>';
            }
        }

        // copy array with array_merge so we don't sort global array
        $sorted_countries = array_merge(array(), $all_countries);
        asort($sorted_countries);

        foreach($sorted_countries as $abbr => $title) :
            echo '<option value="' . esc_attr($abbr) . '"' . selected($abbr, $select, false) . '>' . esc_html($title) . '</option>';
        endforeach;
        echo '</select>';

        $output = ob_get_clean();

        if($print) {
            echo $output;
            return;
        }

        return $output;
    }

    static function get_country_flag($country) {
        return '<span class="flag ' . esc_attr($country) . '"><br/></span>';
    }

    static function get_country_title($country) {
        $all_countries = self::all_countries();
        if(isset($all_countries[$country])) {
            return $all_countries[$country];
        }
        return false;
    }

    static function date_array2time_helper($date)
    {
        if(is_array($date) && isset($date['hh'], $date['mn'], $date['mm'], $date['jj'], $date['yy'])) {
            return mktime((int)$date['hh'], (int)$date['mn'], 0, (int)$date['mm'], (int)$date['jj'], (int)$date['yy']);
        }
        return $date;
    }

};


