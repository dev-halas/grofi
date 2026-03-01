<div class="product_description">

    <?php 
        $prod_desc = get_field('prod_desc');
        if( $prod_desc ):
            foreach( $prod_desc as $row ):
                switch( $row['acf_fc_layout'] ):
                    
                    case 'image_full':
                        if( !empty( $row['image_full_img'] ) ): ?>
                            <img 
                                src="<?php echo esc_url( $row['image_full_img']['url'] ); ?>" 
                                alt="<?php echo esc_attr( $row['image_full_img']['alt'] ); ?>"
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

                endswitch;
            endforeach;
        endif; ?>

</div>