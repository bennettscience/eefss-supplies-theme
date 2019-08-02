<!-- Teacher Contact Modal Starts -->
<div class="modal fade" id="teacherContact" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Bootstrap Modal Title</h4>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <?php gravity_form(3, $display_title=false, $display_description=true,$display_inactive=false, $field_values=null, $ajax=true,$tabindex, $echo=true); ?>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" class="btn btn-outline">Close</button>
            </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ends -->

<!-- Request Item Modal Starts -->

<?php if(get_post_type() == 'eefss_warehouse_ad') {
			$quant = get_field('quantity');

			echo '<div class="modal fade" id="requestItem" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<!-- Modal Header -->
					<div class="modal-header">
						<h4 class="modal-title">Request Item</h4>
					</div>
					<!-- Modal Body -->
					<div class="modal-body">
						<h2>Available: </h2>
						<label>Quantity:
							<input class="form-control" placeholder="1" id="quant" type="number" min="1" max="' . $quant . '" value="" />
						</label>
						<button class="btn btn-primary mb-2" id="request-item-btn" data-id="'. $post->ID .'">Request Item</button>
						<span id="response"></span>
					</div>
					<!-- Modal Footer -->
					<div class="modal-footer">
						<button type="button" data-dismiss="modal" class="btn">Close</button>
					</div>
					</div>
				</div>
			</div>
		</div>';

		}
		?>

<!-- Modal Ends -->
    