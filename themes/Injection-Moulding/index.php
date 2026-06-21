<?php
/**
 * Main Index Template (fallback for blog/archive pages)
 */
get_header(); ?>

<main style="max-width:1280px;margin:120px auto 80px;padding:0 16px;">
    <h1 class="section-title" style="margin-bottom:40px;"><?php wp_title(''); ?></h1>

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('why-card'); ?> style="margin-bottom:32px;">
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('large', ['style' => 'width:100%;border-radius:12px;margin-bottom:16px;']); ?>
                </a>
            <?php endif; ?>
            <h2 style="font-size:24px;font-family:'Montserrat',sans-serif;font-weight:700;color:#19191A;margin-bottom:12px;">
                <a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a>
            </h2>
            <p style="color:#767C8C;font-size:14px;font-family:'Poppins',sans-serif;margin-bottom:12px;">
                <?php echo get_the_date(); ?> &bull; <?php the_author(); ?>
            </p>
            <div class="entry-content"><?php the_excerpt(); ?></div>
            <a href="<?php the_permalink(); ?>" class="btn-primary" style="margin-top:16px;display:inline-flex;">
                Read More &rarr;
            </a>
        </article>
    <?php endwhile;
        the_posts_pagination();
    else : ?>
        <p style="font-size:18px;color:#767C8C;">No content found.</p>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
