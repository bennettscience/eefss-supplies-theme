<!-- Modal Starts -->
    <div class="modal fade" id="bootstrapModal" role="dialog">
        <div class="modal-dialog" role="document">
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
                    <button type="button" data-dismiss="modal" class="btn btn-default">Close</button>
                </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Ends -->