<?php
/*
    WP-Clanwars sidebar widget
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

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

require_once (dirname(__FILE__) . '/classes/utils.class.php');

use \WP_Clanwars\Utils;

class WP_ClanWars_Widget extends WP_Widget {

    const ONE_DAY = 60 * 60 * 24;

    var $default_settings = array();
    var $newer_than_options = array();

    function __construct()
    {
        $wp_theme = wp_get_theme();
        $wp_theme_name = $wp_theme->get_template();
        $theme_class = 'widget_clanwars_' . $wp_theme_name;

        $widget_ops = array(
            'classname' => 'widget_clanwars ' . $theme_class,
            'description' => __('ClanWars widget', WP_CLANWARS_TEXTDOMAIN)
        );
        parent::__construct('clanwars', __('ClanWars', WP_CLANWARS_TEXTDOMAIN), $widget_ops);

        $this->default_settings = array(
            'title' => __('ClanWars', WP_CLANWARS_TEXTDOMAIN),
            'display_both_teams' => false,
            'display_game_icon' => true,
            'show_limit' => 10,
            'hide_title' => false,
            'hide_older_than' => '1m',
            'custom_hide_duration' => 0,
            'visible_games' => array()
        );

        $this->newer_than_options = array(
            'custom' => array('title' => __('Custom', WP_CLANWARS_TEXTDOMAIN), 'value' => 0),
            'all' => array('title' => __('Show all', WP_CLANWARS_TEXTDOMAIN), 'value' => 0),
            '1d' => array('title' => __('1 day', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY),
            '2d' => array('title' => __('2 days', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 2),
            '3d' => array('title' => __('3 days', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 3),
            '1w' => array('title' => __('1 week', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 7),
            '2w' => array('title' => __('2 weeks', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 14),
            '3w' => array('title' => __('3 weeks', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 21),
            '1m' => array('title' => __('1 month', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 30),
            '2m' => array('title' => __('2 months', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 30 * 2),
            '3m' => array('title' => __('3 months', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 30 * 3),
            '6m' => array('title' => __('6 months', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 30 * 6),
            '1y' => array('title' => __('1 year', WP_CLANWARS_TEXTDOMAIN), 'value' => self::ONE_DAY * 30 * 12)
        );

        wp_register_script('jquery-cookie', WP_CLANWARS_URL . '/js/jquery.cookie.pack.js', array('jquery'), WP_CLANWARS_VERSION);
        wp_register_script('wp-clanwars-tabs', WP_CLANWARS_URL . '/js/tabs.js', array('jquery', 'jquery-cookie'), WP_CLANWARS_VERSION);
        wp_register_script('wp-clanwars-widget-admin', WP_CLANWARS_URL . '/js/widget-admin.js', array('jquery'), WP_CLANWARS_VERSION);

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    private function _sort_games($a, $b) {
        $t1 = mysql2date('U', $a->date);
        $t2 = mysql2date('U', $b->date);

        if($t1 == $t2) {
            return 0;
        }

        return ($t1 > $t2) ? -1 : 1;
    }

    function admin_enqueue_scripts() {
        wp_enqueue_script('wp-clanwars-widget-admin');
    }

    function widget($args, $instance) {
        wp_enqueue_script('wp-clanwars-tabs');

        extract($args);

        $now = Utils::current_time_fixed('timestamp');

        $instance = wp_parse_args((array)$instance, $this->default_settings);

        $title = apply_filters('widget_title', empty($instance['title']) ? __('ClanWars', WP_CLANWARS_TEXTDOMAIN) : $instance['title']);

        $matches = array();
        $games = array();

        $options = array(
            'id' => empty($instance['visible_games']) ? 'all' : $instance['visible_games'],
            'orderby' => 'title',
            'order' => 'asc'
        );
        $_games = \WP_Clanwars\Games::get_game($options);

        $from_date = 0;
        if(isset($this->newer_than_options[$instance['hide_older_than']])) {
            $age = (int) $this->newer_than_options[$instance['hide_older_than']]['value'];

            if( $instance['hide_older_than'] === 'custom' ) { // custom
                $age = (int) $instance['custom_hide_duration'] * self::ONE_DAY;
                if($age > 0) {
                    $from_date = $now - $age;
                }
            }
            else if( $age > 0 ) { // 0 means show all matches
                $from_date = $now - $age;
            }
        }

        foreach($_games as $game) {
            $options = array(
                'from_date' => $from_date,
                'game_id' => $game->id,
                'limit' => $instance['show_limit'],
                'order' => 'desc',
                'orderby' => 'date',
                'sum_tickets' => true
            );

            $matchResult = \WP_Clanwars\Matches::get_match( $options );

            if( $matchResult->count() ) {
                $games[] = $game;
                $matches = array_merge( $matches, $matchResult->getArrayCopy() );
            }
        }

        usort( $matches, array($this, '_sort_games') );

        ?>

        <?php echo $before_widget; ?>
        <?php if ( $title && !$instance['hide_title'] )
            echo $before_title . $title . $after_title; ?>

<ul class="clanwar-list<?php if($instance['display_game_icon']) echo ' shows-game-icon'; ?>">

    <?php if(sizeof($games) > 1) : ?>
    <li>
        <ul class="tabs">
        <?php
        $obj = new stdClass();
        $obj->id = 0;
        $obj->title = __('All', WP_CLANWARS_TEXTDOMAIN);
        $obj->abbr = __('All');
        $obj->icon = 0;

        array_unshift($games, $obj);

        for($i = 0; $i < sizeof($games); $i++) :
            $game = $games[$i];
            $link = ($game->id == 0) ? 'all' : 'game-' . $game->id;
        ?>
            <li<?php if($i == 0) echo ' class="selected"'; ?>><a href="#<?php echo $link; ?>" title="<?php echo esc_attr($game->title); ?>"><?php echo esc_html($game->abbr); ?></a></li>
        <?php endfor; ?>
        </ul>
    </li>
    <?php endif; ?>

    <?php foreach($matches as $i => $match) :
            $is_upcoming = false;
            $t1 = $match->team1_tickets;
            $t2 = $match->team2_tickets;
            $wld_class = $t1 == $t2 ? 'draw' : ($t1 > $t2 ? 'win' : 'loss');
            $date = mysql2date(get_option('date_format') . ', ' . get_option('time_format'), $match->date);
            $timestamp = mysql2date('U', $match->date);

            $game_icon = wp_get_attachment_url($match->game_icon);

            $is_upcoming = $timestamp > $now;
            $is_playing = ($now > $timestamp && $now < ($timestamp + 3600));

            $item_classes = [ 'clanwar-item', 'game-' . $match->game_id ];

            if($i % 2 != 0) {
                $item_classes[] = 'alt';
            }
    ?>
    <li class="<?php echo esc_attr( join(' ', $item_classes) ); ?>">

            <?php if($is_upcoming) : ?>
            <div class="upcoming"><?php _e('Upcoming', WP_CLANWARS_TEXTDOMAIN); ?></div>
            <?php elseif($is_playing) : ?>
            <div class="live"><?php _e('Live', WP_CLANWARS_TEXTDOMAIN); ?></div>
            <?php else : ?>
            <div class="scores <?php echo $wld_class; ?>"><?php echo sprintf(__('%d:%d', WP_CLANWARS_TEXTDOMAIN), $t1, $t2); ?></div>
            <?php endif; ?>

            <div class="opponent-team">
            <?php if( $instance['display_game_icon'] && $game_icon !== false ) : ?>
            <img src="<?php echo $game_icon; ?>" alt="<?php echo esc_attr($match->game_title); ?>" class="icon" />
            <?php endif; ?>

            <?php
                $team1_flag = \WP_Clanwars\Utils::get_country_flag($match->team1_country);
                $team2_flag = \WP_Clanwars\Utils::get_country_flag($match->team2_country);

                if($instance['display_both_teams']) {
                    $team_title = sprintf('%s %s vs. %s %s',
                        $team1_flag, esc_html($match->team1_title),
                        $team2_flag, esc_html($match->team2_title)
                    );
                }
                else {
                    $team_title = sprintf('%s %s', $team2_flag, esc_html($match->team2_title));
                }

                if($match->post_id != 0) {
                    echo sprintf('<a href="%s" title="%s">%s</a>', get_permalink($match->post_id), esc_attr($match->title), $team_title);
                }
                else {
                    echo $team_title;
                }
            ?>
            </div>
            <div class="date"><?php echo $date; ?></div>

    </li>
        <?php endforeach; ?>
</ul>

            <?php echo $after_widget; ?>

        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = wp_parse_args( (array)$new_instance, $this->default_settings );

        $instance['show_limit'] = abs((int)$instance['show_limit']);
        $instance['custom_hide_duration'] = abs((int)$instance['custom_hide_duration']);
        $instance['display_game_icon'] = array_key_exists('display_game_icon', (array)$new_instance);

        return $instance;
    }

    function form($instance) {
        $instance = wp_parse_args( (array)$instance, $this->default_settings );
        $games = \WP_Clanwars\Games::get_game('id=all&orderby=title&order=asc');

    ?>

        <div class="wp-clanwars-widget-settings">

            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', WP_CLANWARS_TEXTDOMAIN); ?></label>
                <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" type="text" />
            </p>

            <p>
                <input class="checkbox" name="<?php echo $this->get_field_name('hide_title'); ?>" id="<?php echo $this->get_field_id('hide_title'); ?>" value="1" type="checkbox" <?php checked($instance['hide_title'], true)?>/> <label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide title', WP_CLANWARS_TEXTDOMAIN); ?></label>
            </p>

            <p>
                <input class="checkbox"
                        name="<?php echo $this->get_field_name('display_both_teams'); ?>"
                        id="<?php echo $this->get_field_id('display_both_teams'); ?>"
                        value="1"
                        type="checkbox" <?php checked($instance['display_both_teams'], true)?>/>&nbsp;
                <label for="<?php echo $this->get_field_id('display_both_teams'); ?>">
                    <?php _e('Display both teams', WP_CLANWARS_TEXTDOMAIN); ?>
                </label>
            </p>

            <p>
                <input class="checkbox"
                        name="<?php echo $this->get_field_name('display_game_icon'); ?>"
                        id="<?php echo $this->get_field_id('display_game_icon'); ?>"
                        value="1" type="checkbox" <?php checked($instance['display_game_icon'], true)?>/>&nbsp;
                <label for="<?php echo $this->get_field_id('display_game_icon'); ?>">
                    <?php _e('Display game icon', WP_CLANWARS_TEXTDOMAIN); ?>
                </label>
            </p>

            <p><?php _e('Show games:', WP_CLANWARS_TEXTDOMAIN); ?></p>
            <p>
                <?php foreach($games as $item) : ?>
                <label for="<?php echo $this->get_field_id('visible_games-' . $item->id); ?>"><input type="checkbox" name="<?php echo $this->get_field_name('visible_games'); ?>[]" id="<?php echo $this->get_field_id('visible_games-' . $item->id); ?>" value="<?php echo esc_attr($item->id); ?>" <?php checked(true, in_array($item->id, $instance['visible_games'])); ?>/> <?php echo esc_html($item->title); ?></label><br/>
                <?php endforeach; ?>
            </p>
            <p><?php _e('Do not check any game if you want to show all games.', WP_CLANWARS_TEXTDOMAIN); ?></p>

            <p>
                <label for="<?php echo $this->get_field_id('show_limit'); ?>"><?php _e('Show matches:', WP_CLANWARS_TEXTDOMAIN); ?></label>
                <input type="text" size="3" name="<?php echo $this->get_field_name('show_limit'); ?>" id="<?php echo $this->get_field_id('show_limit'); ?>" value="<?php echo esc_attr($instance['show_limit']); ?>" />
            </p>

            <p class="widget-setting-hide-older-than">
                <label for="<?php echo $this->get_field_id('hide_older_than'); ?>"><?php _e('Hide matches older than', WP_CLANWARS_TEXTDOMAIN); ?></label>
                <select name="<?php echo $this->get_field_name('hide_older_than'); ?>" id="<?php echo $this->get_field_id('hide_older_than'); ?>">
                    <?php foreach($this->newer_than_options as $key => $option) : ?>
                        <option value="<?php echo esc_attr($key); ?>"<?php selected($key, $instance['hide_older_than']); ?>><?php echo esc_html($option['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="widget-setting-custom-hide-duration <?php if( $instance['hide_older_than'] !== 'custom' ) echo esc_attr('hidden'); ?>">
                <label for="<?php echo $this->get_field_id('custom_hide_duration'); ?>"><?php _e('Custom (days): ', WP_CLANWARS_TEXTDOMAIN); ?></label>
                <input type="text" size="3" name="<?php echo $this->get_field_name('custom_hide_duration'); ?>" id="<?php echo $this->get_field_id('custom_hide_duration'); ?>" value="<?php echo esc_attr($instance['custom_hide_duration']); ?>" />
            </p>

        </div>

    <?php
    }
}

?>