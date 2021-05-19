<div id="sunshine" class="sunshine-clearfix <?php sunshine_classes(); ?>">

	<?php do_action('sunshine_before_content'); ?>

	
	<div id="sunshine-main">

		<form method="post" action="" id="sunshine-cart">
		<input type="hidden" name="sunshine_update_cart" value="1" />
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sunshine_update_cart' ); ?>" />

		<?php do_action('sunshine_before_cart_items'); ?>

		<?php if (sunshine_cart_items()) { ?>
			<table id="sunshine-cart-items">
			<tr>
				<th class="sunshine-cart-image"><?php _e('Image', 'sunshine'); ?></th>
				<th class="sunshine-cart-name"><?php _e('Product', 'sunshine'); ?></th>
				<th class="sunshine-cart-name"><?php _e('Comments', 'sunshine'); ?></th>
				<th class="sunshine-cart-qty"><?php _e('Qty', 'sunshine'); ?></th>
			</tr>
			<?php $i = 1; $tabindex = 0; foreach (sunshine_cart_items() as $item) { $tabindex++; ?>
				<tr class="sunshine-cart-item <?php sunshine_product_class($item['product_id']); ?>">
					<td class="sunshine-cart-item-image" data-label="<?php _e('Image', 'sunshine'); ?>">
						<?php
						$thumb = wp_get_attachment_image_src($item['image_id'], 'sunshine-thumbnail');
						$image_html = '<a href="'.get_permalink($item['image_id']).'"><img src="'.$thumb[0].'" alt="" class="sunshine-image-thumb" /></a>';
						echo apply_filters('sunshine_cart_image_html', $image_html, $item, $thumb);
						?>
					</td>
					<td>
					</td>
					<td class="sunshine-cart-item-name"">
						<div class="sunshine-item-comments"><?php echo apply_filters('sunshine_cart_item_comments', $item['comments'], $item); ?></div>
					</td>
					<td class="sunshine-cart-item-qty" data-label="<?php _e('Qty', 'sunshine'); ?>">
						<a href="?delete_cart_item=<?php echo $item['hash']; ?>&nonce=<?php echo wp_create_nonce( 'sunshine_delete_cart_item' ); ?>"><?php _e('Remove','sunshine'); ?></a>
					</td>
				</tr>

			<?php $i++; } ?>
			</table>

			<?php do_action('sunshine_after_cart_items'); ?>

			<div id="sunshine-cart-update-button">
				<input type="submit" value="<?php _e('Update Cart', 'sunshine'); ?>" class="sunshine-button-alt" />
			</div>

			</form>

			<?php do_action('sunshine_after_cart_form'); ?>

			<div id="sunshine-cart-totals">
				<p id="sunshine-cart-checkout-button"><a href="<?php echo sunshine_url('checkout'); ?>" class="sunshine-button"><?php _e('Continue to checkout', 'sunshine'); ?> &rarr;</a></p>
			</div>

			<script>
			jQuery(document).ready(function($){
				var sunshine_cart_change = false;
				$('#sunshine input').change(function(){
					sunshine_cart_change = true;
				});
				$('#sunshine-cart-checkout-button a').click(function(){
					if ( sunshine_cart_change ) {
						var r = confirm( '<?php _e( 'You have changed items in your cart but have not yet updated. Do you want to continue to checkout?', 'sunshine' ); ?>');
						if ( !r ) {
							return false;
						}
					}
				});
			});
			</script>

		<?php } else { ?>
			<p><?php _e('You do not have anything in your cart yet!', 'sunshine'); ?></p>
		<?php } ?>

		<?php do_action('sunshine_after_cart'); ?>

	</div>

	<?php do_action('sunshine_after_content'); ?>

</div>
