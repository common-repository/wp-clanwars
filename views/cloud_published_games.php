<div class="wrap wp-clanwars-cloud-page">

    <?php $partial('partials/cloud_nav', compact( 'active_tab', 'cloud_account', 'logged_into_cloud', 'search_query' )); ?>

    <?php if ( isset( $api_error_message ) ) : ?>
    <?php $partial( 'partials/browse_games_error', compact( 'api_error_message' ) ) ?>
    <?php endif; ?>

    <?php if ( empty($api_games) ) : ?>
    <p class="wp-clanwars-api-error"><?php _e( 'No games found.', WP_CLANWARS_TEXTDOMAIN ); ?></p>
    <?php endif; ?>

    <ul class="wp-clanwars-cloud-items wp-clanwars-clearfix" id="wp-clanwars-cloud-items">

    <?php

    foreach ( $api_games as $game ) :

        $item_classes = array( 'wp-clanwars-cloud-item' );

        if( $logged_into_cloud && !property_exists($game, 'vote') ) {
            $item_classes[] = 'wp-clanwars-cloud-item-votes-enabled';
        }

        if( $logged_into_cloud && property_exists($game, 'vote') ) {
            $item_classes[] = 'wp-clanwars-cloud-item-voted';
        }

        $item_classes[] = $game->approved ? 'wp-clanwars-cloud-item-approved' : 'wp-clanwars-cloud-item-pending';

    ?>
    <li class="<?php echo esc_attr(join(' ', $item_classes)) ?>" data-remote-id="<?php echo esc_attr($game->_id); ?>">
        <div class="wp-clanwars-review-status"><?php
            if($game->approved) :
                _e('Approved', WP_CLANWARS_TEXTDOMAIN);
            else :
                _e('Pending review', WP_CLANWARS_TEXTDOMAIN);
            endif; ?></div>
        <?php $partial('partials/browse_game_item', compact('game', 'install_action', 'logged_into_cloud')); ?>
    </li>
    <?php endforeach; ?>

    </ul>

</div>