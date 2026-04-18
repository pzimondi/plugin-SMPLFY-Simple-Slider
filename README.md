# BLIKSEM Simple Slider

## DESCRIPTION
SMPLFY Simple Slider is a lightweight WordPress plugin that integrates the popular bxSlider library to create responsive, customizable image sliders on your WordPress site. It also includes a **Testimonial Slider** feature with a custom post type for managing intern/client testimonials.

This plugin relies on the bxSlider jQuery plugin for its core functionality, providing features like touch-enabled navigation, infinite looping, and adaptive height.

## FEATURES

- **Responsive Design**: Sliders automatically adjust to different screen sizes and devices.
- **Lightweight**: Minimal impact on site performance, enqueuing scripts only where needed.
- **bxSlider Powered**: Leverages the reliable bxSlider library for smooth animations.
- **Testimonial Slider**: Custom Post Type with shortcode support for displaying testimonials.

## INSTALLATION
1. Download the plugin ZIP file from the GitHub repository.
2. In your WordPress admin dashboard, go to Plugins > Add New > Upload Plugin.
3. Upload the ZIP file and activate the plugin.
4. Alternatively, clone the repository into your `/wp-content/plugins/` directory and activate via the Plugins page.

**Note**: This plugin requires WordPress version 5.5 or higher and a theme that supports jQuery.

## USAGE

### Image Sliders
Add the class `bxslider` to any container with child elements:
```html
<div class="bxslider">
    <div><img src="/path/to/image1.jpg" /></div>
    <div><img src="/path/to/image2.jpg" /></div>
    <div><img src="/path/to/image3.jpg" /></div>
</div>
```

### Testimonial Slider

**Step 1: Add Testimonials**
1. In your WordPress admin, go to **Testimonials > Add New Testimonial**
2. Enter the intern/client name as the **Title**
3. Upload their photo as the **Featured Image**
4. Fill in the **Country** and **Testimonial Quote** fields
5. Click **Publish**

**Step 2: Display the Slider**
Add this shortcode to any page, post, or widget:
```
[simplify_testimonials]
```

**Shortcode Options:**
```
[simplify_testimonials posts_per_page="3"]     Show only 3 testimonials
[simplify_testimonials order="ASC"]             Show oldest first
[simplify_testimonials orderby="title"]         Sort alphabetically by name
```

## FILE STRUCTURE
```
plugin-SMPLFY-Simple_Slider-main/
├── bliksem-simple-slider.php    Main plugin file
├── css/
│   ├── bxslider.css             bxSlider library styles
│   ├── bx_slider_css.min.css    Minified bxSlider styles
│   ├── bs-testimonials.css      Testimonial slider styles
│   └── fonts/                   Slider icon fonts
├── js/
│   ├── bxslider.js              bxSlider library (don't modify)
│   └── rx.js                    Slider initialization
├── assets/                      Sample images
├── Example.html                 Basic slider example
└── README.md                    This file
```

## FAQs

**Does this plugin require any other libraries?**
Yes, it uses bxSlider (included). Ensure jQuery is loaded on your site (most WordPress themes include it by default).

**Can I customize the testimonial styling?**
Yes! Override the classes prefixed with `.bs-` in your theme's CSS. The dark background color is set on `.bs-testimonial-section`.

**What if no photo is uploaded for a testimonial?**
The slider will display a placeholder circle with the person's initials instead.

**Is it compatible with Gutenberg?**
Yes, use the shortcode in a Shortcode block or Classic block.

## CREDITS

- Developed by Andre Nell.
- Powered by [bxSlider](https://bxslider.com/) by Steven Wanderski.
