<?php
/**
 * Hero Slider
 *
 * ACF: dodaj repeater 'slider_slides' na stronie głównej lub w Głównych ustawieniach.
 * Pola dla każdego slajdu:
 *   slide_title       (text)
 *   slide_description (textarea)
 *   slide_button_text (text)
 *   slide_button_url  (url)
 *   slide_image       (image — zwraca array)
 *   slide_bg_color    (color picker, domyślnie #f38f1c)
 */

$slides = get_field('hero');

if ( empty( $slides ) ) {
	return;
}
?>

<section class="heroSlider">
  <div class="container">
    <div class="swiper heroSlider__swiper">
      <div class="swiper-wrapper">

        <?php foreach ( $slides as $slide ) :
          $title = $slide['hero_title'];
          $desc  = $slide['hero_description'];
          $link  = $slide['hero_url'];
          $image = $slide['hero_image'];
        ?>

          <div class="swiper-slide heroSlider__slide">
            <div class="heroSlider__inner">

              <div class="heroSlider__content">
                <?php if ( $title ) : ?>
                  <div class="heroSlider__title"><?php echo $title; ?></div>
                <?php endif; ?>

                <?php if ( $desc ) : ?>
                  <div class="heroSlider__desc"><?php echo $desc; ?></div>
                <?php endif; ?>

                <?php if ( $link ) : ?>
                  <a href="<?php echo esc_url( $link['url'] ); ?>" class="button button--outline">
                    <?php echo esc_html( $link['title'] ); ?>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                      <path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                <?php endif; ?>
              </div>

              <?php if ( $image ) : ?>
                <div class="heroSlider__image">
                  <img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>">
                </div>
              <?php endif; ?>

            </div>
          </div>

        <?php endforeach; ?>

      </div>

      <div class="heroSlider__navContainer">
        <button class="heroSlider__nav heroSlider__nav--prev" aria-label="Poprzedni slajd">
          <svg xmlns="http://www.w3.org/2000/svg" width="23" height="12" viewBox="0 0 23 12" fill="none">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.20587 10.8348C5.85717 11.1852 5.29063 11.1862 4.94025 10.8377L0.263489 6.18345L0.262513 6.18247C-0.0869594 5.83376 -0.0880332 5.26575 0.262513 4.91587L0.262513 4.91489L4.94025 0.260599C5.29058 -0.0878435 5.85718 -0.0868688 6.20587 0.263528C6.55438 0.613822 6.55302 1.18046 6.20294 1.52915L3.06232 4.65415L22.0184 4.65415C22.5123 4.6544 22.9126 5.05472 22.9129 5.54869C22.9129 6.04287 22.5125 6.44395 22.0184 6.44419L3.06232 6.44419L6.20294 9.56919C6.55305 9.91789 6.55441 10.4845 6.20587 10.8348Z" fill="#FF9100"/>
          </svg>
        </button>

        <button class="heroSlider__nav heroSlider__nav--next" aria-label="Następny slajd">
          <svg xmlns="http://www.w3.org/2000/svg" width="23" height="12" viewBox="0 0 23 12" fill="none">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M16.707 0.263539C17.0557 -0.0868178 17.6223 -0.0878927 17.9727 0.260609L22.6494 4.91491L22.6504 4.91588C22.9999 5.26459 23.0009 5.83261 22.6504 6.18248V6.18346L17.9727 10.8378C17.6223 11.1862 17.0557 11.1852 16.707 10.8348C16.3585 10.4845 16.3599 9.91789 16.71 9.5692L19.8506 6.4442L0.894531 6.4442C0.400566 6.44396 0.000259433 6.04363 0 5.54967C0 5.05549 0.400406 4.65441 0.894531 4.65416L19.8506 4.65416L16.71 1.52916C16.3599 1.18046 16.3585 0.613836 16.707 0.263539Z" fill="#FF9100"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</section>
