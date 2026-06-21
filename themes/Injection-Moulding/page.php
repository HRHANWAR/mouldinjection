<?php
/**
 * Standard Page Template
 */
get_header(); ?>

<!-- Wrapper for full viewport height control -->
<div class="site-main-wrapper" style="display: flex; flex-direction: column; min-height: 100vh;">

    <main id="primary" class="site-main" style="flex: 1 0 auto; padding-top: 140px; padding-bottom: 80px;">
        <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 24px;">

            <?php while ( have_posts() ) : the_post(); ?>
                
                <article id="post-<?php the_ID(); ?>" <?php post_class('reveal'); ?>>
                    
                    <!-- Page Header: Title & Meta -->
                    <header class="entry-header" style="margin-bottom: 40px;">
                        <h1 class="entry-title" style="font-size: clamp(32px, 5vw, 48px); color: #FFFFFF; font-weight: 700;">
                            <?php the_title(); ?>
                        </h1>
                    </header>

                    <!-- Featured Image: Only shows if admin sets one -->
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="post-thumbnail" style="margin-bottom: 48px; border-radius: 24px; overflow: hidden;">
                            <?php the_post_thumbnail('full', ['style' => 'width:100%; height:auto; display:block;']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Content: This is where admin's Editor content appears -->
                    <div class="entry-content" style="font-size: 18px; line-height: 1.8; color: rgba(255,255,255,0.9);">
                        <?php 
                            the_content(); 
                            
                            // For paginated posts
                            wp_link_pages( array(
                                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'text-domain' ),
                                'after'  => '</div>',
                            ) );
                        ?>
                    </div>

                </article>

            <?php endwhile; ?>

        </div>
    </main>

</div>

<?php get_footer(); ?>
