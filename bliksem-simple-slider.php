<?php
/**
 * Plugin Name:       Simplify Frontend
 * Description:       Plugin implementing BX Slider on Wordpress
 * Version:           1.1.0
 * Author:            Ferrin Mutuku
 * Author URI:        https://simplifybiz.com/
 * Requires at least  5.5
 * Requires Plugins:  smplfy-core
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

/* Enqueue CSS and JS files
==================================== */

add_action('wp_enqueue_scripts', 'bs_enqueue_styles');
function bs_enqueue_styles()
{
    // Slider library CSS
    wp_register_style('bx_slider_css', plugin_dir_url(__FILE__) . 'css/bxslider.css');
    wp_enqueue_style('bx_slider_css');

    // Testimonial styles (loads AFTER bxslider.css so our overrides win)
    wp_register_style('bs_testimonials_css', plugin_dir_url(__FILE__) . 'css/bs-testimonials.css', array('bx_slider_css'), '1.0');
    wp_enqueue_style('bs_testimonials_css');

    // Benefits section styles
    wp_register_style('bs_benefits_css', plugin_dir_url(__FILE__) . 'css/bs-benefits.css', array(), '1.0');
    wp_enqueue_style('bs_benefits_css');

    // Job listing styles
    wp_register_style('bs_job_css', plugin_dir_url(__FILE__) . 'css/bs-job-listing.css', array(), '1.0');
    wp_enqueue_style('bs_job_css');

    // Apply section styles
    wp_register_style('bs_apply_css', plugin_dir_url(__FILE__) . 'css/bs-apply.css', array(), '1.0');
    wp_enqueue_style('bs_apply_css');

    // Slider library JS
    wp_register_script('bx_slider_js', plugin_dir_url(__FILE__) . 'js/bxslider.js', array('jquery'), '1.0', true);
    wp_register_script('rx_js', plugin_dir_url(__FILE__) . 'js/rx.js', array('jquery', 'bx_slider_js'), '1.0', true);

    wp_enqueue_script('bx_slider_js');
    wp_enqueue_script('rx_js');

    // Job listing animations
    wp_register_script('bs_job_anim_js', plugin_dir_url(__FILE__) . 'js/bs-job-animations.js', array(), '1.0', true);
    wp_enqueue_script('bs_job_anim_js');
}

/* Register Testimonial Custom Post Type
==================================== */

add_action('init', 'bs_register_testimonial_cpt');
function bs_register_testimonial_cpt()
{
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'add_new'            => 'Add New Testimonial',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'all_items'          => 'All Testimonials',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-format-quote',
        'supports'           => array('title', 'thumbnail'),
        'has_archive'        => false,
    );

    register_post_type('bs_testimonial', $args);
}

/* Add Testimonial Meta Boxes
==================================== */

add_action('add_meta_boxes', 'bs_add_testimonial_meta_boxes');
function bs_add_testimonial_meta_boxes()
{
    add_meta_box(
        'bs_testimonial_details',
        'Testimonial Details',
        'bs_testimonial_meta_callback',
        'bs_testimonial',
        'normal',
        'high'
    );
}

function bs_testimonial_meta_callback($post)
{
    // Security nonce
    wp_nonce_field('bs_save_testimonial', 'bs_testimonial_nonce');

    // Get existing values
    $country = get_post_meta($post->ID, '_bs_country', true);
    $quote   = get_post_meta($post->ID, '_bs_quote', true);

    ?>
    <p>
        <label for="bs_country"><strong>Country</strong></label><br>
        <input type="text"
               id="bs_country"
               name="bs_country"
               value="<?php echo esc_attr($country); ?>"
               style="width: 100%; max-width: 400px;"
               placeholder="e.g. Kenya, South Africa, Zimbabwe">
    </p>
    <p>
        <label for="bs_quote"><strong>Testimonial Quote</strong></label><br>
        <textarea id="bs_quote"
                  name="bs_quote"
                  rows="6"
                  style="width: 100%;"
                  placeholder="Enter the intern's testimonial here..."><?php echo esc_textarea($quote); ?></textarea>
    </p>
    <?php
}

/* Save Testimonial Meta Data
==================================== */

add_action('save_post_bs_testimonial', 'bs_save_testimonial_meta');
function bs_save_testimonial_meta($post_id)
{
    // Verify nonce
    if (!isset($_POST['bs_testimonial_nonce']) ||
        !wp_verify_nonce($_POST['bs_testimonial_nonce'], 'bs_save_testimonial')) {
        return;
    }

    // Don't save during autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permission
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save country
    if (isset($_POST['bs_country'])) {
        update_post_meta($post_id, '_bs_country', sanitize_text_field($_POST['bs_country']));
    }

    // Save quote
    if (isset($_POST['bs_quote'])) {
        update_post_meta($post_id, '_bs_quote', sanitize_textarea_field($_POST['bs_quote']));
    }
}

/* Testimonial Slider Shortcode
==================================== */

add_shortcode('simplify_testimonials', 'bs_testimonial_shortcode');
function bs_testimonial_shortcode($atts)
{
    // Allow shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ), $atts);

    // Query testimonials
    $query = new WP_Query(array(
        'post_type'      => 'bs_testimonial',
        'posts_per_page' => $atts['posts_per_page'],
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'post_status'    => 'publish',
    ));

    // If no testimonials found, return nothing
    if (!$query->have_posts()) {
        return '<p>No testimonials found.</p>';
    }

    // Start building the HTML output
    ob_start();
    ?>

    <section class="bs-testimonial-section">
        <div class="bs-testimonial-container">

            <div class="bs-testimonial-header">
                <span class="bs-section-label bs-animate">What our interns say</span>
                <h2 class="bs-section-title bs-animate bs-animate-delay-1">Real stories from real growth.</h2>
                <p class="bs-section-subtitle bs-animate bs-animate-delay-2">
                    Our interns don't just learn — they build, ship, and grow.
                </p>
            </div>

            <div class="bs-testimonial-slider bs-animate bs-animate-delay-3">
                <?php while ($query->have_posts()) : $query->the_post();
                    $country  = get_post_meta(get_the_ID(), '_bs_country', true);
                    $quote    = get_post_meta(get_the_ID(), '_bs_quote', true);
                    $name     = get_the_title();
                    $initials = bs_get_initials($name);
                ?>

                <div class="bs-testimonial-slide">
                    <div class="bs-testimonial-card">

                        <p class="bs-testimonial-quote">
                            <?php echo esc_html($quote); ?>
                        </p>

                        <div class="bs-testimonial-footer">
                            <?php if (has_post_thumbnail()) : ?>
                                <img class="bs-testimonial-avatar"
                                     src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>"
                                     alt="<?php echo esc_attr($name); ?>">
                            <?php else : ?>
                                <div class="bs-avatar-placeholder">
                                    <?php echo esc_html($initials); ?>
                                </div>
                            <?php endif; ?>

                            <div class="bs-testimonial-info">
                                <span class="bs-testimonial-name"><?php echo esc_html($name); ?></span>
                                <?php if ($country) : ?>
                                    <span class="bs-testimonial-location"><?php echo esc_html($country); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <?php endwhile; ?>
            </div>

        </div>
    </section>

    <?php
    wp_reset_postdata();

    return ob_get_clean();
}

/* Helper: Get initials from a name
==================================== */

function bs_get_initials($name)
{
    $words = explode(' ', trim($name));
    $initials = '';

    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }

    return substr($initials, 0, 2);
}

/* Benefits Section Shortcode
==================================== */

add_shortcode('simplify_benefits', 'bs_benefits_shortcode');
function bs_benefits_shortcode($atts)
{
    $benefits = array(
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M8 12l3 3 5-5"/></svg>',
            'title' => 'Hands-On Experience',
            'text'  => 'Work on real-world projects and live applications, not simulations. You\'ll ship code that actual users interact with from day one.',
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'title' => 'Portfolio Building',
            'text'  => 'Leave with a portfolio of real projects you can show employers. WordPress sites, custom plugins and design work all yours to keep.',
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'title' => '1-on-1 Mentorship',
            'text'  => 'Get personal guidance from experienced developers and designers. Real feedback on your actual work every week, not group lectures.',
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
            'title' => 'Flexible Remote Work',
            'text'  => 'Work from anywhere. Our interns join from Kenya, South Africa, Zimbabwe and beyond. All you need is a laptop and commitment.',
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
            'title' => 'Career Growth',
            'text'  => 'Strengthen problem-solving and communication skills that employers value. Many of our interns move into full-time roles after completing the program.',
        ),
    );

    ob_start();
    ?>

    <section class="bs-benefits-section">
        <div class="bs-benefits-container">

            <div class="bs-benefits-header">
                <div class="bs-benefits-label bs-animate">Why choose us</div>
                <h2 class="bs-benefits-title bs-animate bs-animate-delay-1">Why intern for Simplify Biz?</h2>
                <p class="bs-benefits-subtitle bs-animate bs-animate-delay-2">
                    More than an internship. It's a launchpad for your career in tech.
                </p>
            </div>

            <div class="bs-benefits-grid">
                <?php for ($i = 0; $i < 3; $i++) : ?>
                <div class="bs-benefit-card">
                    <div class="bs-benefit-icon">
                        <?php echo $benefits[$i]['icon']; ?>
                    </div>
                    <h3><?php echo esc_html($benefits[$i]['title']); ?></h3>
                    <p><?php echo esc_html($benefits[$i]['text']); ?></p>
                </div>
                <?php endfor; ?>
            </div>

            <div class="bs-benefits-row-2">
                <?php for ($i = 3; $i < 5; $i++) : ?>
                <div class="bs-benefit-card">
                    <div class="bs-benefit-icon">
                        <?php echo $benefits[$i]['icon']; ?>
                    </div>
                    <h3><?php echo esc_html($benefits[$i]['title']); ?></h3>
                    <p><?php echo esc_html($benefits[$i]['text']); ?></p>
                </div>
                <?php endfor; ?>
            </div>

        </div>
    </section>

    <?php
    return ob_get_clean();
}

/* Apply for Open Roles Shortcode
==================================== */

add_shortcode('simplify_apply', 'bs_apply_shortcode');
function bs_apply_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'designer_url'   => '#',
        'social_url'     => '#',
        'analyst_url'    => '#',
        'developer_url'  => '#',
    ), $atts);

    $roles = array(
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>',
            'title' => 'Junior Web Designer',
            'desc'  => 'UI/UX design, wireframes and visual design for WordPress sites',
            'url'   => $atts['designer_url'],
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
            'title' => 'Junior Social Media Specialist',
            'desc'  => 'Content creation, strategy and community management',
            'url'   => $atts['social_url'],
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
            'title' => 'Junior Business Analyst',
            'desc'  => 'Requirements gathering, process mapping and data analysis',
            'url'   => $atts['analyst_url'],
        ),
        array(
            'icon'  => '<svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            'title' => 'Junior Web Developer',
            'desc'  => 'WordPress development, custom plugins and theme building',
            'url'   => $atts['developer_url'],
        ),
    );

    ob_start();
    ?>

    <section class="bs-apply-section">
        <div class="bs-apply-container">

            <div class="bs-apply-header">
                <div class="bs-apply-label">Join the team</div>
                <h2 class="bs-apply-title">Apply for Open Roles</h2>
                <p class="bs-apply-subtitle">
                    Ready to start building your future? Pick a role and apply today.
                </p>
            </div>

            <div class="bs-apply-grid">
                <?php foreach ($roles as $role) : ?>
                <a href="<?php echo esc_url($role['url']); ?>" class="bs-role-card">
                    <div class="bs-role-icon">
                        <?php echo $role['icon']; ?>
                    </div>
                    <h3><?php echo esc_html($role['title']); ?></h3>
                    <p><?php echo esc_html($role['desc']); ?></p>
                    <span class="bs-role-cta">
                        Apply now
                        <svg viewBox="0 0 24 24" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <?php
    return ob_get_clean();
}

/* ============================================================
   JOB LISTING: Junior Web Developer Internship
   ============================================================ */

add_shortcode('simplify_job_webdev', 'bs_job_webdev_shortcode');
function bs_job_webdev_shortcode()
{
    $svg_check = '<span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;min-width:20px;flex-shrink:0;margin-top:2px;background:#22c55e;border-radius:4px;transform:rotate(45deg)"><span style="display:block;width:7px;height:7px;background:#fff;border-radius:1px;transform:rotate(-45deg)"></span></span>';

    ob_start();
    ?>

    <!-- HERO -->
    <div class="bs-job-hero bs-job-hero-webdev bs-hero-lighttrails">
        <canvas class="bs-hero-canvas"></canvas>
        <div class="bs-job-hero-container">
            <div class="bs-job-badge">Accepting Applications</div>

            <div class="bs-terminal">
                <div class="bs-terminal-bar">
                    <span class="bs-terminal-dot red"></span>
                    <span class="bs-terminal-dot yellow"></span>
                    <span class="bs-terminal-dot green"></span>
                </div>
                <div class="bs-terminal-body">
                    <div class="bs-terminal-line"><span class="bs-comment-mark">// </span><span class="bs-typed-text" style="color:#64748b">Junior Web Developer Internship</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">role</span><span class="bs-tag-value">: fullstack WordPress developer</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">location</span><span class="bs-tag-value">: fully remote</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">duration</span><span class="bs-tag-value">: 14 weeks</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">hours</span><span class="bs-tag-value">: flexible schedule</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">pay</span><span class="bs-tag-value">: based on location</span></div>
                    <div class="bs-terminal-line"><span class="bs-tag-key">stack</span><span class="bs-tag-value">: PHP, WordPress, Flutter, REST API</span></div>
                </div>
            </div>

            <div class="bs-job-hero-meta" style="margin-top: 28px;">
                <span class="bs-job-meta-item">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Fully Remote
                </span>
                <span class="bs-job-meta-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    14 Weeks
                </span>
                <span class="bs-job-meta-item">
                    <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Flexible Hours
                </span>
                <span class="bs-job-meta-item">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Paid
                </span>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="bs-job-content bs-job-hero-webdev">

        <!-- About -->
        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                <h2>About the Opportunity</h2>
            </div>
            <p>Join our distributed development team for a flexible, remote internship where you'll gain real world experience building custom WordPress solutions and mobile applications. This is a hands on opportunity to develop professional skills in fullstack development while working on production projects.</p>
        </div>

        <!-- Work On + Tech Stack -->
        <div class="bs-job-two-col">
            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
                    <h2>What You'll Work On</h2>
                </div>
                <div class="bs-numbered-list">
                    <div class="bs-numbered-item"><div class="bs-step-number">01</div><span>Develop custom WordPress plugins from scratch</span></div>
                    <div class="bs-numbered-item"><div class="bs-step-number">02</div><span>Test and debug applications across platforms</span></div>
                    <div class="bs-numbered-item"><div class="bs-step-number">03</div><span>Build mobile apps for Android using Flutter that integrate with WordPress via REST API</span></div>
                    <div class="bs-numbered-item"><div class="bs-step-number">04</div><span>Work with modern tools including PHPStorm, GitHub and AI assisted development with Claude</span></div>
                </div>
            </div>

            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    <h2>Tech Stack</h2>
                </div>
                <div class="bs-tech-row"><div class="bs-tech-row-label">Backend</div><div class="bs-tech-grid"><span class="bs-tech-tag">PHP</span><span class="bs-tech-tag">WordPress</span><span class="bs-tech-tag">REST API</span></div></div>
                <div class="bs-tech-row"><div class="bs-tech-row-label">Frontend</div><div class="bs-tech-grid"><span class="bs-tech-tag">JavaScript</span><span class="bs-tech-tag">HTML</span><span class="bs-tech-tag">CSS</span></div></div>
                <div class="bs-tech-row"><div class="bs-tech-row-label">Mobile</div><div class="bs-tech-grid"><span class="bs-tech-tag">Flutter</span></div></div>
                <div class="bs-tech-row"><div class="bs-tech-row-label">Tools</div><div class="bs-tech-grid"><span class="bs-tech-tag">PHPStorm</span><span class="bs-tech-tag">GitHub</span><span class="bs-tech-tag">Claude AI</span></div></div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
                <h2>What We Are Looking For</h2>
            </div>
            <div class="bs-job-sublabel">Required</div>
            <div class="bs-check-list">
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon"><?php echo $svg_check; ?></div>Currently pursuing an Associate or Bachelor's in Information Technology or Software Development through BYU Pathway Worldwide</div>
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon"><?php echo $svg_check; ?></div>Strong foundation in Object Oriented Programming</div>
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon"><?php echo $svg_check; ?></div>Ability to learn and use AI tools (Claude) effectively in development workflow</div>
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon"><?php echo $svg_check; ?></div>Self directed and comfortable working remotely with minimal supervision</div>
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon"><?php echo $svg_check; ?></div>Programming fluency (we'll teach you WordPress and PHP specifics)</div>
            </div>
            <div class="bs-job-sublabel bonus">Bonus</div>
            <div class="bs-check-list">
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon bonus"><?php echo $svg_check; ?></div>Experience with Flutter</div>
                <div class="bs-check-item" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;font-size:1.05rem;line-height:1.6;color:#334155;"><div class="bs-check-icon bonus"><?php echo $svg_check; ?></div>Previous experience with version control (Git/GitHub)</div>
            </div>
        </div>

        <!-- Internship Details -->
        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <h2>Internship Details</h2>
            </div>
            <div class="bs-details-grid">
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="bs-detail-label">Duration</div><div class="bs-detail-value">14 Weeks</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="bs-detail-label">Hours</div><div class="bs-detail-value">Flexible schedule</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="bs-detail-label">Location</div><div class="bs-detail-value">Fully remote</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="bs-detail-label">Start Date</div><div class="bs-detail-value">Every semester</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="bs-detail-label">Compensation</div><div class="bs-detail-value">Paid (based on location)</div></div></div>
            </div>
        </div>

        <!-- What You'll Gain -->
        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <h2>What You'll Gain</h2>
            </div>
            <div class="bs-gain-grid">
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Fullstack development on real production projects</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Working with distributed teams across time zones</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Async communication and collaboration skills</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Mentorship in WordPress custom development</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Mobile app integration with web platforms</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Portfolio worthy projects</div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><div class="bs-gain-icon"><?php echo $svg_check; ?></div>Potential for fulltime employment</div>
            </div>
        </div>

        <!-- About Us + CTA -->
        <div class="bs-job-about">
            <h2>About Simplify Biz</h2>
            <p>We specialize in custom WordPress development, creating tailored solutions for our clients including websites, plugins and integrated mobile applications. Our remote first team values independence, problem solving ability and continuous learning.</p>
            <a href="https://intern.simplifybiz.com/internship-opportunities-junior-web-developer/" class="bs-apply-job-btn">
                Apply Now
                <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        <p class="bs-job-footer-note">This internship is designed for BYU Pathway Worldwide students pursuing degrees in Information Technology or Software Development.</p>

    </div>

    <?php
    return ob_get_clean();
}

/* ============================================================
   JOB LISTING: Junior Web Designer Internship
   ============================================================ */

add_shortcode('simplify_job_designer', 'bs_job_designer_shortcode');
function bs_job_designer_shortcode()
{
    $svg_arrow = '<svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
    $svg_star = '<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

    ob_start();
    ?>

    <!-- HERO -->
    <div class="bs-job-hero bs-job-hero-designer bs-hero-orbitrings">
        <canvas class="bs-hero-canvas"></canvas>
        <div class="bs-job-hero-container">
            <div class="bs-job-badge">Accepting Applications</div>
            <h1>Junior Web <em>Designer</em> Internship</h1>
            <div class="bs-job-hero-underline"></div>
            <div class="bs-job-hero-meta">
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Fully Remote</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>14 Weeks</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Flexible Hours</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Paid</span>
            </div>
        </div>
    </div>

    <div class="bs-job-content bs-job-hero-designer">

        <div class="bs-job-section">
            <h2>About the Opportunity</h2>
            <p>Join our distributed development team for a flexible, remote internship where you'll design and build beautiful, functional websites for real clients. This role bridges design and development. You'll create the visual experience and bring it to life with code. Perfect for designers who love to build, or developers with a strong design sense.</p>
        </div>

        <div class="bs-job-section">
            <h2>What You'll Work On</h2>
            <div class="bs-tilt-grid">
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div><div class="bs-tilt-text"><strong>Website Design and Build</strong>Design and build websites using WordPress and page builders from concept to launch</div></div>
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/></svg></div><div class="bs-tilt-text"><strong>UX and Visual Design</strong>Create visually compelling, user friendly web experiences with strong typography and color systems</div></div>
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div><div class="bs-tilt-text"><strong>Frontend Code</strong>Enhance functionality with custom JavaScript and CSS. Implement responsive designs across all devices</div></div>
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="bs-tilt-text"><strong>Team Collaboration</strong>Collaborate with developers on integrated web and mobile app experiences</div></div>
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg></div><div class="bs-tilt-text"><strong>Design Assets</strong>Use Adobe Creative Suite and Figma to create mockups, prototypes and brand assets</div></div>
                <div class="bs-tilt-card"><div class="bs-tilt-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div><div class="bs-tilt-text"><strong>AI Assisted Design</strong>Use AI tools like Claude and ChatGPT to accelerate content production and streamline workflows</div></div>
            </div>
        </div>

        <div class="bs-job-two-col">
            <div class="bs-job-section">
                <h2>Tech Stack and Tools</h2>
                <div class="bs-tool-row"><div class="bs-tool-category">Design</div><div class="bs-tool-palette"><span class="bs-tool-chip">Adobe Creative Suite</span><span class="bs-tool-chip">Figma</span></div></div>
                <div class="bs-tool-row"><div class="bs-tool-category">Build</div><div class="bs-tool-palette"><span class="bs-tool-chip">WordPress Blocks</span></div></div>
                <div class="bs-tool-row"><div class="bs-tool-category">Code</div><div class="bs-tool-palette"><span class="bs-tool-chip">HTML</span><span class="bs-tool-chip">CSS</span><span class="bs-tool-chip">JavaScript</span></div></div>
                <div class="bs-tool-row"><div class="bs-tool-category">Dev Tools</div><div class="bs-tool-palette"><span class="bs-tool-chip">PHPStorm</span><span class="bs-tool-chip">GitHub</span><span class="bs-tool-chip">Claude AI</span></div></div>
            </div>
            <div class="bs-job-section">
                <h2>Internship Details</h2>
                <div class="bs-details-grid">
                    <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="bs-detail-label">Duration</div><div class="bs-detail-value">14 Weeks</div></div></div>
                    <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="bs-detail-label">Hours</div><div class="bs-detail-value">Flexible</div></div></div>
                    <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="bs-detail-label">Location</div><div class="bs-detail-value">Fully remote</div></div></div>
                    <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="bs-detail-label">Pay</div><div class="bs-detail-value">Based on location</div></div></div>
                </div>
            </div>
        </div>

        <div class="bs-job-section">
            <h2>What We're Looking For</h2>
            <div class="bs-designer-sublabel required">Must Have</div>
            <div class="bs-arrow-list">
                <div class="bs-arrow-item"><span class="bs-arrow-icon"><?php echo $svg_arrow; ?></span>Completed at least 80 credits towards your degree with emphasis on design or development</div>
                <div class="bs-arrow-item"><span class="bs-arrow-icon"><?php echo $svg_arrow; ?></span>Understanding of design and UX principles</div>
                <div class="bs-arrow-item"><span class="bs-arrow-icon"><?php echo $svg_arrow; ?></span>Ability to write custom CSS and JavaScript to enhance user experience</div>
                <div class="bs-arrow-item"><span class="bs-arrow-icon"><?php echo $svg_arrow; ?></span>Self directed and comfortable working remotely with minimal supervision</div>
                <div class="bs-arrow-item"><span class="bs-arrow-icon"><?php echo $svg_arrow; ?></span>Eye for detail and commitment to quality</div>
            </div>
            <div class="bs-designer-sublabel bonus">Nice to Have</div>
            <div class="bs-star-list">
                <div class="bs-star-item"><span class="bs-star-icon"><?php echo $svg_star; ?></span>Familiarity with design tools like Adobe Creative Suite</div>
                <div class="bs-star-item"><span class="bs-star-icon"><?php echo $svg_star; ?></span>Basic PHP knowledge</div>
                <div class="bs-star-item"><span class="bs-star-icon"><?php echo $svg_star; ?></span>Familiar with WordPress</div>
            </div>
        </div>

        <div class="bs-job-section">
            <h2>What You Will Gain</h2>
            <div class="bs-gain-grid">
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Real client work for your portfolio</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Balancing design aesthetics with technical implementation</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Working with distributed teams across time zones</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Hands on experience with Adobe Creative Suite</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Understanding how design integrates with web and mobile</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Mentorship in professional web design and frontend development</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Potential for fulltime employment after completion</span></div>
            </div>
        </div>

        <div class="bs-ideal-card">
            <h2>The Ideal Candidate</h2>
            <p>You're someone who loves both sides of the creative process. Designing beautiful interfaces and writing the code to make them work. You care about the details, understand that good design serves the user, and you're excited to learn and grow in a real world environment.</p>
        </div>

        <div class="bs-job-about">
            <h2>About Simplify Biz</h2>
            <p>We specialize in custom WordPress development, creating tailored solutions for our clients including websites, plugins and integrated mobile applications. Our remote first team values independence, creativity and the ability to transform ideas into exceptional user experiences.</p>
            <a href="https://intern.simplifybiz.com/internship-opportunities-junior-web-designer/" class="bs-apply-job-btn">Apply Now <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <p class="bs-job-footer-note">This internship is designed for BYU Pathway Worldwide students pursuing degrees in Information Technology or Software Development.</p>
    </div>

    <?php
    return ob_get_clean();
}

/* ============================================================
   JOB LISTING: Junior Social Media Specialist Internship
   ============================================================ */

add_shortcode('simplify_job_social', 'bs_job_social_shortcode');
function bs_job_social_shortcode()
{
    $svg_sparkle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6L12 2z"/></svg>';

    ob_start();
    ?>

    <!-- HERO -->
    <div class="bs-job-hero bs-job-hero-social bs-hero-floatshapes">
        <canvas class="bs-hero-canvas"></canvas>
        <div class="bs-job-hero-container">
            <div class="bs-job-badge">Accepting Applications</div>
            <h1>Junior Social Media Specialist Internship</h1>
            <div class="bs-job-hero-underline"></div>
            <div class="bs-job-hero-meta">
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Fully Remote</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>14 Weeks</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Flexible Hours</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Paid</span>
            </div>
        </div>
    </div>

    <div class="bs-job-content bs-job-hero-social">

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                <h2>About the Opportunity</h2>
            </div>
            <p>Join our remote, distributed team as a Junior Social Media Specialist and gain real world experience managing digital marketing strategies for a WordPress development agency specializing in business applications. This internship offers hands on training in content creation, audience engagement, analytics and social media campaign execution across multiple platforms.</p>
            <p style="margin-top: 14px; color: #f97316; font-weight: 500;">If you're passionate about digital communication, branding and storytelling, this role will help you build the skills needed for a career in modern marketing.</p>
        </div>

        <div class="bs-job-two-col">
            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div>
                    <h2>What You'll Work On</h2>
                </div>
                <div class="bs-task-feed">
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>Plan and schedule weekly content calendars</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>Create content for Facebook, Instagram, LinkedIn and YouTube</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>Analyze engagement performance and create simple reports</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>Grow online presence across key channels</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>Use AI tools (Claude, ChatGPT) to accelerate content production</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg></div>Create short form video content (optional but encouraged)</div>
                    <div class="bs-task-row"><div class="bs-task-row-icon"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>Develop campaigns that support product launches and promotions</div>
                </div>
            </div>

            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    <h2>Your Toolkit</h2>
                </div>
                <div class="bs-social-sublabel must">Platforms</div>
                <div class="bs-platform-grid">
                    <div class="bs-platform-card facebook"><div class="bs-platform-icon"><svg viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></div><div class="bs-platform-name">Facebook</div></div>
                    <div class="bs-platform-card instagram"><div class="bs-platform-icon"><svg viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></div><div class="bs-platform-name">Instagram</div></div>
                    <div class="bs-platform-card linkedin"><div class="bs-platform-icon"><svg viewBox="0 0 24 24" fill="#0A66C2"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></div><div class="bs-platform-name">LinkedIn</div></div>
                    <div class="bs-platform-card youtube"><div class="bs-platform-icon"><svg viewBox="0 0 24 24" fill="#FF0000"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></div><div class="bs-platform-name">YouTube</div></div>
                </div>
                <div class="bs-social-sublabel must" style="margin-top: 28px;">Tools and Software</div>
                <div class="bs-social-tool-grid">
                    <span class="bs-social-tool-chip">Canva</span><span class="bs-social-tool-chip">Google Sheets</span><span class="bs-social-tool-chip">WordPress</span><span class="bs-social-tool-chip">Buffer / Meta Business Suite</span><span class="bs-social-tool-chip">Claude AI</span>
                </div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
                <h2>What We Are Looking For</h2>
            </div>
            <div class="bs-social-sublabel must">Must Have</div>
            <div class="bs-signal-list">
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Currently pursuing a degree in Applied Business Management, Marketing or a related program through BYU Pathway Worldwide</div>
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Strong written communication skills</div>
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Basic understanding of social media platforms</div>
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Ability to learn and use AI tools (Claude) to generate and refine content</div>
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Self driven, organized and able to work independently</div>
                <div class="bs-signal-item"><span class="bs-signal-dot"></span>Willingness to learn marketing fundamentals</div>
            </div>
            <div class="bs-social-sublabel extra">Stand Out With</div>
            <div class="bs-sparkle-list">
                <div class="bs-sparkle-item"><span class="bs-sparkle-icon"><?php echo $svg_sparkle; ?></span>Familiarity with content scheduling tools</div>
                <div class="bs-sparkle-item"><span class="bs-sparkle-icon"><?php echo $svg_sparkle; ?></span>Experience with Canva or graphic design tools</div>
                <div class="bs-sparkle-item"><span class="bs-sparkle-icon"><?php echo $svg_sparkle; ?></span>Experience creating short video content (Reels/Shorts/TikTok)</div>
                <div class="bs-sparkle-item"><span class="bs-sparkle-icon"><?php echo $svg_sparkle; ?></span>Understanding of branding or digital marketing principles</div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <h2>Internship Details</h2>
            </div>
            <div class="bs-details-grid">
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="bs-detail-label">Duration</div><div class="bs-detail-value">14 Weeks</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="bs-detail-label">Hours</div><div class="bs-detail-value">Flexible schedule</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="bs-detail-label">Location</div><div class="bs-detail-value">Fully remote</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="bs-detail-label">Start Date</div><div class="bs-detail-value">Every semester</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="bs-detail-label">Compensation</div><div class="bs-detail-value">Based on country of residence</div></div></div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <h2>What You'll Gain</h2>
            </div>
            <div class="bs-gain-grid">
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Real marketing experience supporting a global tech agency</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Skills in social media strategy, analytics and multichannel planning</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Confidence creating professional content for public audiences</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Experience using AI tools to accelerate content production</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>A polished marketing portfolio showcasing your work</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Potential pathway to fulltime employment</span></div>
            </div>
        </div>

        <div class="bs-job-about">
            <h2>About Simplify Biz</h2>
            <p>We are a remote first WordPress development agency specializing in custom business applications including sales automation systems, operations management tools and integrated mobile apps. Our team values creativity, communication and growth.</p>
            <a href="https://intern.simplifybiz.com/internship-opportunities-junior-social-media-specialist/" class="bs-apply-job-btn">Apply Now <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <p class="bs-job-footer-note">This internship is open exclusively to BYU Pathway Worldwide students pursuing business, marketing or communication related degrees.</p>
    </div>

    <?php
    return ob_get_clean();
}

/* ============================================================
   JOB LISTING: Junior Business Analyst Internship
   ============================================================ */

add_shortcode('simplify_job_analyst', 'bs_job_analyst_shortcode');
function bs_job_analyst_shortcode()
{
    ob_start();
    ?>

    <!-- HERO -->
    <div class="bs-job-hero bs-job-hero-analyst bs-hero-waveripple">
        <canvas class="bs-hero-canvas"></canvas>
        <div class="bs-job-hero-container">
            <div class="bs-job-badge">Accepting Applications</div>
            <h1>Junior Business Analyst Internship</h1>
            <div class="bs-job-hero-underline"></div>
            <div class="bs-job-hero-meta">
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Fully Remote</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>14 Weeks</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Flexible Hours</span>
                <span class="bs-job-meta-item"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Paid</span>
            </div>
        </div>
    </div>

    <div class="bs-job-content bs-job-hero-analyst">

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                <h2>About the Opportunity</h2>
            </div>
            <p>Join our fully remote team as a Junior Business Analyst and gain hands on experience supporting the operations of a modern WordPress development agency. This internship provides real exposure to business strategy, project coordination, client workflows and the systems behind delivering custom business applications.</p>
            <p style="margin-top: 14px; color: #3b82f6; font-weight: 500;">If you're interested in business operations, management and process optimization, this role will help you develop practical, industry ready skills.</p>
        </div>

        <div class="bs-job-two-col">
            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <h2>What You'll Work On</h2>
                </div>
                <div class="bs-process-list">
                    <div class="bs-process-item"><span>Assist in coordinating and monitoring task progress</span></div>
                    <div class="bs-process-item"><span>Support business operations and internal workflow improvements</span></div>
                    <div class="bs-process-item"><span>Help document Standard Operating Procedures (SOPs)</span></div>
                    <div class="bs-process-item"><span>Analyze and refine business processes and client onboarding steps</span></div>
                    <div class="bs-process-item"><span>Work with CRM systems to manage pipelines and followup workflows</span></div>
                    <div class="bs-process-item"><span>Assist with scheduling, team coordination and communication</span></div>
                    <div class="bs-process-item"><span>Prepare simple performance, workflow or client activity reports</span></div>
                    <div class="bs-process-item"><span>Use AI tools (Claude) to improve documentation and streamline processes</span></div>
                </div>
            </div>

            <div class="bs-job-section">
                <div class="bs-job-section-header">
                    <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    <h2>Tools and Systems</h2>
                </div>
                <div class="bs-tool-structured">
                    <div class="bs-tool-block"><div class="bs-tool-block-name">Google Workspace</div><div class="bs-tool-block-desc">Docs, Sheets, Drive</div></div>
                    <div class="bs-tool-block"><div class="bs-tool-block-name">Notion or Trello</div><div class="bs-tool-block-desc">Task management</div></div>
                    <div class="bs-tool-block"><div class="bs-tool-block-name">CRM Tools</div><div class="bs-tool-block-desc">Sales automation</div></div>
                    <div class="bs-tool-block"><div class="bs-tool-block-name">WordPress Admin</div><div class="bs-tool-block-desc">Client and internal operations</div></div>
                </div>
                <div class="bs-analyst-skills-label" style="margin-top: 28px;">Skills You'll Develop</div>
                <div class="bs-skills-grid">
                    <div class="bs-skill-card">Project coordination and task management</div>
                    <div class="bs-skill-card">Process mapping and workflow automation</div>
                    <div class="bs-skill-card">Documentation and SOP development</div>
                    <div class="bs-skill-card">Business communication and stakeholder coordination</div>
                    <div class="bs-skill-card">Analytical thinking and data interpretation</div>
                </div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
                <h2>What We're Looking For</h2>
            </div>
            <div class="bs-analyst-sublabel must">Must Have</div>
            <div class="bs-connector-list">
                <div class="bs-connector-item">Currently pursuing a degree in Applied Business Management, Information Systems or a related program through BYU Pathway Worldwide</div>
                <div class="bs-connector-item">Strong organizational skills and attention to detail</div>
                <div class="bs-connector-item">Ability to learn and use AI tools (Claude) to support business operations</div>
                <div class="bs-connector-item">Comfortable working independently and managing time effectively</div>
                <div class="bs-connector-item">Good written and verbal communication skills</div>
                <div class="bs-connector-item">Interest in business systems, processes and optimization</div>
            </div>
            <div class="bs-analyst-sublabel extra">Stand Out With</div>
            <div class="bs-tag-list">
                <div class="bs-tag-item"><span class="bs-tag-icon">Plus</span>Experience with spreadsheets (Google Sheets or Excel)</div>
                <div class="bs-tag-item"><span class="bs-tag-icon">Plus</span>Familiarity with CRMs or workflow management tools</div>
                <div class="bs-tag-item"><span class="bs-tag-icon">Plus</span>Previous experience in administration, customer service or operations</div>
                <div class="bs-tag-item"><span class="bs-tag-icon">Plus</span>Basic understanding of project management principles</div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <h2>Internship Details</h2>
            </div>
            <div class="bs-details-grid">
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="bs-detail-label">Duration</div><div class="bs-detail-value">14 Weeks</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="bs-detail-label">Hours</div><div class="bs-detail-value">Flexible schedule</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="bs-detail-label">Location</div><div class="bs-detail-value">Fully remote</div></div></div>
                <div class="bs-detail-item"><div class="bs-detail-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="bs-detail-label">Start Date</div><div class="bs-detail-value">Every semester</div></div></div>
            </div>
        </div>

        <div class="bs-job-section">
            <div class="bs-job-section-header">
                <div class="bs-job-section-icon"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <h2>What You'll Gain</h2>
            </div>
            <div class="bs-gain-grid">
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Real world experience in business operations and project management</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Hands on exposure to agency workflows and distributed team collaboration</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Skills in documenting processes, managing tasks and contributing to internal systems</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Practical understanding of CRM tools and reporting automation</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Mentorship in operations and business management best practices</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Portfolio ready documents and workflow optimizations</span></div>
                <div class="bs-gain-item" style="display:flex;align-items:flex-start;gap:12px;padding:18px 20px;border:1px solid #f1f5f9;border-radius:12px;font-size:1rem;color:#334155;line-height:1.55;"><span>Potential pathway to fulltime employment upon completion</span></div>
            </div>
        </div>

        <div class="bs-job-about">
            <h2>About Simplify Biz</h2>
            <p>We are a WordPress development agency specializing in building custom business applications including sales automation platforms, operations management tools and integrated mobile systems. Our remote first culture supports independence, initiative and continuous learning.</p>
            <a href="https://intern.simplifybiz.com/internship-opportunities-junior-business-manager/" class="bs-apply-job-btn">Apply Now <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <p class="bs-job-footer-note">This internship is designed for BYU Pathway Worldwide students pursuing degrees in Business Management, Information Systems or related programs.</p>
    </div>

    <?php
    return ob_get_clean();
}
