<div class="wrap wp-clanwars-onboarding-page setup-games">

    <h1><?php _e( 'Get started with WP-Clanwars', WP_CLANWARS_TEXTDOMAIN ); ?></h1>

    <div class="left-column">

        <h2><?php _e( 'Install games', WP_CLANWARS_TEXTDOMAIN ); ?></h2>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">

            <input type="hidden" name="action" value="wp-clanwars-setupgames" />

            <?php wp_nonce_field('wp-clanwars-setupgames'); ?>

            <p><?php _e( 'Choose one of the options:', WP_CLANWARS_TEXTDOMAIN ); ?></p>

            <fieldset>
                <p><label for="upload"><input type="radio" name="import" id="upload" value="upload" checked="checked" /> <?php _e('Upload previously saved game (ZIP file)', WP_CLANWARS_TEXTDOMAIN); ?></label></p>
                <p><input type="file" name="userfile" /></p>
            </fieldset>

            <fieldset>
                <p><label for="create"><input type="radio" name="import" id="create" value="create" /> <?php _e('Create a new game', WP_CLANWARS_TEXTDOMAIN); ?></label></p>
                <p><?php _e("<i>Not finding your game?</i> You can create your own!<br/>We'll take you to map editing right away.", WP_CLANWARS_TEXTDOMAIN); ?></p>
                <p><input type="text" class="game-name" name="new_game_name" placeholder="<?php _e("Type in a new game's name", WP_CLANWARS_TEXTDOMAIN); ?>" /></p>
            </fieldset>

            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php echo esc_attr($page_submit); ?>" />
            </p>

        </form>

    </div><!-- .left-column -->

    <div class="right-column">

        <div class="wp-filter">
            <form class="search-form" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
                <input type="hidden" name="page" value="wp-clanwars-cloud" />
                <input type="search" name="q" value="<?php if(isset($search_query)) echo esc_attr($search_query); ?>" class="wp-filter-search" placeholder="<?php esc_attr_e( 'Search Games', WP_CLANWARS_TEXTDOMAIN ); ?>" />
            </form>
        </div>

        <?php if ( isset( $api_error_message ) ) : ?>
        <?php $partial( 'partials/browse_games_error', compact( 'api_error_message' ) ) ?>
        <?php endif; ?>

        <ul class="wp-clanwars-cloud-items wp-clanwars-clearfix" id="wp-clanwars-cloud-items">

        <?php foreach ( $api_games as $game ) : ?>
        <li class="wp-clanwars-cloud-item">
            <?php $partial('partials/browse_game_item', compact('game', 'install_action')); ?>
        </li>
        <?php endforeach; ?>

        </ul>

    </div><!-- .right-column -->

</div><!-- .wrap -->