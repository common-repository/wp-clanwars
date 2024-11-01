<div class="wp-clanwars-cloud-item-top">
    <div class="wp-clanwars-cloud-item-header wp-clanwars-clearfix">
        <div class="wp-clanwars-cloud-item-column-title">
            <h4>
                <img src="<?php echo esc_attr($game->iconUrl); ?>" alt="<?php echo esc_attr($game->title); ?>" class="wp-clanwars-cloud-item-icon" />
                <span class="game-title"><?php echo esc_html($game->title); ?></span>
            </h4>
        </div>
        <div class="wp-clanwars-cloud-item-column-install">
        <?php if($game->is_installed) : ?>
            <button type="button" class="button wp-clanwars-install-button" disabled="disabled"><?php _e( 'INSTALLED', WP_CLANWARS_TEXTDOMAIN ); ?></button>
        <?php else : ?>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

            <input type="hidden" name="action" value="<?php echo esc_attr( $install_action ); ?>" />
            <input type="hidden" name="remote_id" value="<?php echo esc_attr( $game->_id ); ?>" />

            <?php wp_nonce_field( $install_action ); ?>

            <button type="submit" class="button wp-clanwars-install-button" data-text-toggle="<?php esc_attr_e( 'INSTALL NOW', WP_CLANWARS_TEXTDOMAIN ); ?>"><?php _e( 'GET', WP_CLANWARS_TEXTDOMAIN ); ?></button>

            </form>
        <?php endif; ?>
        </div>
    </div>
    <ul class="maps">
    <?php foreach($game->maps as $map) : ?>
        <li>
            <img class="screenshot" src="<?php echo esc_attr($map->imageUrl); ?>" alt="<?php echo esc_attr($map->title); ?>" draggable="false" />
            <div class="title"><?php echo esc_html($map->title); ?></div>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
<div class="wp-clanwars-cloud-item-bottom">
    <div class="wp-clanwars-cloud-item-column-rating">
        <div class="star-rating">
            <?php
            for($i = 1; $i <= 5; $i++) :
                $star_class = 'empty';

                if( $game->rating >= $i ) {
                    $star_class = 'full';
                }
                else if( ceil($game->rating) >= $i ) {
                    $star_class = 'half';
                }
            ?>
            <div class="star star-<?php echo $star_class; ?>"></div>
            <?php endfor; ?>
        </div>
        <span class="num-ratings"><?php echo sprintf( _x('(%d)', 'Number of ratings', WP_CLANWARS_TEXTDOMAIN), $game->votes ); ?></span>
        <div class="spinner"></div>
    </div>
    <div class="wp-clanwars-cloud-item-column-published">
        <strong><?php _e('Published:', WP_CLANWARS_TEXTDOMAIN); ?></strong>
        <span><?php echo esc_html( mysql2date(get_option('date_format'), $game->createdAt, true) ); ?></span>
    </div>
    <div class="wp-clanwars-cloud-item-column-downloaded"><?php echo sprintf( _nx('%d install', '%d installs', $game->downloads, 'Number of downloads', WP_CLANWARS_TEXTDOMAIN ), $game->downloads ); ?></div>
    <div class="wp-clanwars-cloud-item-column-author">
        <?php echo esc_html($game->owner->fullname); ?>
    </div>
</div>