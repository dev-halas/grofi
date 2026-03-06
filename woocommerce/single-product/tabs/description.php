<div class="product_description">

  <?php 
    function grofi_acf_img_url( $img ) {
      return is_array( $img ) ? $img['url'] : wp_get_attachment_url( $img );
    }
    function grofi_acf_img_alt( $img ) {
      return is_array( $img ) ? $img['alt'] : '';
    }

    $prod_desc = get_field('layout_content');
    if( $prod_desc ):
      foreach( $prod_desc as $row ):
        switch( $row['acf_fc_layout'] ):
            
          case 'image_full':
            if( !empty( $row['image_full_img'] ) ): ?>
              <img 
                  src="<?php echo esc_url( grofi_acf_img_url( $row['image_full_img'] ) ); ?>" 
                  alt="<?php echo esc_attr( grofi_acf_img_alt( $row['image_full_img'] ) ); ?>"
              />
            <?php endif;
            break;

          case 'text':
            if( !empty( $row['text_content'] ) ): ?>
              <div class="text_content">
                  <?php echo $row['text_content']; ?>
              </div>
            <?php endif;
            break;

          case 'photo_text':
            if( !empty( $row['photo_text_img'] ) && !empty( $row['photo_text_content'] ) ): ?>
              <div class="grid grid--cols-2 text_content gap32px align-center">
                <div class="grid-item">
                  <img 
                    src="<?php echo esc_url( grofi_acf_img_url( $row['photo_text_img'] ) ); ?>" 
                    alt="<?php echo esc_attr( grofi_acf_img_alt( $row['photo_text_img'] ) ); ?>"
                  />
                </div>
                <div class="grid-item">
                  <?php echo $row['photo_text_content']; ?>
                </div>
              </div>
            <?php endif;
            break;

          case 'text_photo':
            if( !empty( $row['text_photo_img'] ) && !empty( $row['text_photo_content'] ) ): ?>
              <div class="grid grid--cols-2 text_content gap32px align-center">
                <div class="grid-item">
                  <?php echo $row['text_photo_content']; ?>
                </div>
                <div class="grid-item">
                  <img 
                    src="<?php echo esc_url( grofi_acf_img_url( $row['text_photo_img'] ) ); ?>" 
                    alt="<?php echo esc_attr( grofi_acf_img_alt( $row['text_photo_img'] ) ); ?>"
                  />
                </div>
              </div>
            <?php endif;
            break;

          case 'photo_photo':
            if( !empty( $row['photo_photo_img_1'] ) && !empty( $row['photo_photo_img_2'] ) ): ?>
              <div class="grid grid--cols-2 text_content gap32px align-center">
                <div class="grid-item">
                  <img 
                    src="<?php echo esc_url( grofi_acf_img_url( $row['photo_photo_img_1'] ) ); ?>"
                    alt="<?php echo esc_attr( grofi_acf_img_alt( $row['photo_photo_img_1'] ) ); ?>"
                  />
                </div>
                <div class="grid-item">
                  <img 
                    src="<?php echo esc_url( grofi_acf_img_url( $row['photo_photo_img_2'] ) ); ?>"
                    alt="<?php echo esc_attr( grofi_acf_img_alt( $row['photo_photo_img_2'] ) ); ?>"
                  />
                </div>
              </div>
            <?php endif;
            break;
            
          case 'text_text':
            if( !empty( $row['text_text_content_1'] ) && !empty( $row['text_text_content_2'] ) ): ?>
              <div class="grid grid--cols-2 text_content gap32px align-center">
                <div class="grid-item">
                  <?php echo $row['text_text_content_1']; ?>
                </div>
                <div class="grid-item">
                  <?php echo $row['text_text_content_2']; ?>
                </div>
              </div>
            <?php endif;
            break;

        endswitch;
      endforeach;
    endif; ?>

</div>
