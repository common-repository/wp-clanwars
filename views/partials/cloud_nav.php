<div class="wp-clanwars-clearfix">
    <?php $partial('partials/account_header', compact('cloud_account', 'logged_into_cloud')); ?>
    <h1 class="wp-heading-inline"><?php _e('Clanwars Cloud', WP_CLANWARS_TEXTDOMAIN); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=wp-clanwars-cloud&tab=upload' ); ?>" class="page-title-action"><?php _e('Install from ZIP', WP_CLANWARS_TEXTDOMAIN); ?></a>
    <hr class="wp-header-end" />
</div>

<div class="wp-filter">
    <ul class="filter-links">
        <li>
        <?php if( $active_tab == 'search' ) : ?>
            <a href="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>" class="current"><?php _e( 'Search Results', WP_CLANWARS_TEXTDOMAIN ); ?></a>
        <?php endif; ?>

            <a href="<?php echo admin_url('admin.php?page=wp-clanwars-cloud'); ?>"<?php if($active_tab == 'popular') : ?> class="current"<?php endif; ?>><?php _e( 'Popular', WP_CLANWARS_TEXTDOMAIN ); ?></a>
            <?php if($logged_into_cloud) : ?>
            <a href="<?php echo admin_url('admin.php?page=wp-clanwars-cloud&tab=published'); ?>"<?php if($active_tab == 'published') : ?> class="current"<?php endif; ?>><?php _e( 'Published', WP_CLANWARS_TEXTDOMAIN ); ?></a>
            <?php endif; ?>
            <a href="<?php echo admin_url('admin.php?page=wp-clanwars-cloud&tab=publish'); ?>"<?php if($active_tab == 'publish') : ?> class="current"<?php endif; ?>><?php _e( 'Publish', WP_CLANWARS_TEXTDOMAIN ); ?></a>

        </li>
    </ul>
    <form class="search-form" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
        <input type="hidden" name="page" value="wp-clanwars-cloud" />
        <input type="search" name="q" value="<?php if(isset($search_query)) echo esc_attr($search_query); ?>" class="wp-filter-search" placeholder="<?php echo esc_attr(__('Search Games', WP_CLANWARS_TEXTDOMAIN)); ?>" />
    </form>
</div>