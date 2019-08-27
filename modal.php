<!-- Teacher Contact Modal Starts -->
<div class="modal fade" id="teacherContact" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Contact Teacher</h4>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <?php gravity_form(3, $display_title=false, $display_description=true,$display_inactive=false, $field_values=null, $ajax=true, $tabindex, $echo=true); ?>
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

<!-- Catch Sign In Modal --> 
<div class="modal fade" id="signInPrompt" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Sign In</h4>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
				<p>To request warehouse items, you need to have a Schoolhouse Supply Store account. Please sign in with your account or register a new account to continue.</p>
				<div class="container">
					<div class="row justify-content-center">
						<div class="col-lg-2">
							<a class="btn btn-secondary" href="<?php echo home_url( '/wp-login.php?action=register' ); ?>">Register</a>
						</div>
						<div class="col-lg-2">
							<a class="btn btn-secondary" href="<?php echo wp_login_url($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]); ?>">Log In</a>
						</div>
					</div>
				</div>
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
							<input class="form-control" id="quant" type="number" min="1" max="' . $quant . '" value="" />
						</label>
						<button class="btn btn-primary mb-2" id="request-item-btn" data-id="'. $post->ID .'">Request Item</button>
						<div id="response"></div>
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
    