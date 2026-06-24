<?php
/**
 * Plugin Name: Pawa Partners
 * Description: Renders a responsive card grid of partner organisations via the [pawa_partners] shortcode.
 * Version:     1.0.0
 * Author:      Rahisi Solutions
 * License:     GPL-2.0-or-later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'pawa_partners', 'pawa_partners_shortcode' );

function pawa_partners_shortcode() {
    $page = get_page_by_path( 'partners' );
    if ( ! $page ) {
        return '<p>Partners listing not configured.</p>';
    }

    $partners = get_posts( [
        'post_type'      => 'page',
        'post_parent'    => $page->ID,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ] );

    if ( empty( $partners ) ) {
        return '<p>No partners found.</p>';
    }

    ob_start();
    ?>
    <style>
    .pawa-partners-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin: 2rem 0;
    }
    .pawa-partners-grid .pawa-partner-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: box-shadow .2s, transform .2s;
    }
    .pawa-partners-grid .pawa-partner-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,.14);
        transform: translateY(-3px);
    }
    .pawa-partners-grid .pawa-partner-logo-wrap {
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 1;
    }
    .pawa-partners-grid .pawa-partner-logo-wrap img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 1rem;
    }
    .pawa-partners-grid .pawa-partner-logo-fallback {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #000080;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: 700;
        font-family: Roboto, sans-serif;
        letter-spacing: 1px;
    }
    .pawa-partners-grid .pawa-partner-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .pawa-partners-grid .pawa-partner-name {
        font-family: Roboto, sans-serif;
        font-size: 1rem;
        font-weight: 600;
        color: #000080;
        margin: 0 0 .5rem;
        line-height: 1.3;
    }
    .pawa-partners-grid .pawa-partner-excerpt {
        font-family: Roboto, sans-serif;
        font-size: .875rem;
        color: #7a7a7a;
        flex: 1;
        margin: 0 0 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pawa-partners-grid .pawa-partner-btn {
        display: inline-block;
        padding: .5rem 1.25rem;
        background: #000080;
        color: #fff !important;
        border-radius: 4px;
        text-decoration: none !important;
        font-size: .875rem;
        font-weight: 500;
        font-family: Roboto, sans-serif;
        align-self: flex-start;
        transition: background .2s;
    }
    .pawa-partners-grid .pawa-partner-btn:hover { background: #00006a; }
    @media (max-width: 1024px) {
        .pawa-partners-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 540px) {
        .pawa-partners-grid { grid-template-columns: 1fr; }
    }
    </style>
    <div class="pawa-partners-grid">
    <?php foreach ( $partners as $partner ) :
        $has_thumb = has_post_thumbnail( $partner->ID );
        $words     = preg_split( '/\s+/', $partner->post_title );
        $initials  = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ?? '' ), array_slice( $words, 0, 3 ) ) );
    ?>
        <div class="pawa-partner-card">
            <div class="pawa-partner-logo-wrap">
                <?php if ( $has_thumb ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( $partner->ID, 'medium' ) ); ?>"
                         alt="<?php echo esc_attr( $partner->post_title ); ?> logo">
                <?php else : ?>
                    <div class="pawa-partner-logo-fallback" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
                <?php endif; ?>
            </div>
            <div class="pawa-partner-body">
                <h3 class="pawa-partner-name"><?php echo esc_html( $partner->post_title ); ?></h3>
                <?php if ( $partner->post_excerpt ) : ?>
                    <p class="pawa-partner-excerpt"><?php echo esc_html( $partner->post_excerpt ); ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_permalink( $partner->ID ) ); ?>" class="pawa-partner-btn">View Profile</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
