<style>
    a {
        margin-left: 10px;
    }

    #formContainers2 {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
        background-color: rgba(0, 0, 0, 0.0);
        /* Semi-transparent background */
        z-index: 3;

    }

	#update_unit  {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        width: 400px;

    }

</style>
<style>
  .toggle.ios, .toggle-on.ios, .toggle-off.ios { border-radius: 20rem; }
  .toggle.ios .toggle-handle { border-radius: 20rem; }
</style>

	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
		<!-- Include Select2 JS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
	<script src="assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
	</body>

	<script>

	</script>


<?php include "header.php" ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card my-4">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                                <div class="containers">
                                    <div class="item">
                                        <div>
                                            <h6 class="text-white text-capitalize ps-3"></h6>
                                        </div>
                                    </div>
                                    <div class="divider"></div>
                                    <div class="item">

                                    </div>
                                </div>
                            </div>
                            <div class="card-body px-0 pb-2"> 
                                    <div class="table-responsive p-0">
                                        <table class="table align-items-center mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-uppercase text-secondary  font-weight-bolder "
                                                        style="text-align:center;">
                                                        SN</th>
													<th class="text-uppercase text-secondary  font-weight-bolder "
                                                        style="text-align:left;">
                                                        Item Name</th>
                                                    
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if($items != null){
                                                $i = 1;
                                                foreach ($items as $item) { ?>

												<tr id="row42" style="font-size:20px;">
													<td style="text-align:center; font-size:16px;">
														<?= $i++ ?>
													</td>
													<td style="text-align:left; font-size:16px;">
														<input type="checkbox" class="product-checkbox" value="<?= $item['id']?>" style="margin-right: 20px;"> <?= $item['name'] ?>
													</td>
												</tr>
												<?php }}?>

											   <button id="bulkUpdateBtn">update</button>
                                        </tbody>


                                    </table>
                                </div>
                            </div>
                            <div class="card-footer clearfix">
                                <div class="pagination  text-center">

                                    <div style='margin-top: 10px;'>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

		<div id="formContainers2">
			<form method="post" class="slip" enctype="multipart/form-data" id="update_unit" action="addleadsourceform">
				<div class="card-header">
					<h5>Select Unit</h5>
				</div>
				<div class="card-body pt-0">
					<div class="row">
						<div class="col-lg-6">
							<div class="form-group">
								<label class="form-control-label" for="input-username">Base Unit</label>
								<select style="width: 100%;" name="unit" id="baseunit">
									<option value="">-- Select Base Unit --</option>
									<?php foreach ($units as $unit) { ?>
										<option value="<?= $unit['id'] ?>"><?= $unit['uqc'] ?></option>
									<?php } ?>
								</select>
							</div>
							<br>
						</div>

						<div class="col-lg-6">
							<div class="form-group">
								<label class="form-control-label" for="input-username">Secondary Unit</label>
								<select style="width: 100%;"  name="unit" id="secondary_unit" >
									<option value="">-- Select Secondary Unit --</option>
									<?php foreach ($units as $unit) { ?>
										<option value="<?= $unit['id'] ?>"><?= $unit['uqc'] ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
					</div>
					<br>

					<div id="conversion_rate" style="display: none;">
						<p>Conversion Rate</p>
						<span>1 </span><span id="base_unit_name"></span> <span> =</span> 
						<input id="conversion_value" style="width:70px;" type="number">
						<span id="secondary_unit_name"></span>
					</div>

				</div>
				<div class="card-footer text-end">
					<button class="btn bg-success text-white btn-sm float-end mt-6 mb-0" type="submit" style="margin-left:2px" id="saveBtn">Save</button>
					<button class="btn bg-danger text-white btn-sm float-end mt-6 mb-0" type="button" id="cancel2">Cancel</button>
				</div>
			</form>
		</div>



		
		<script>
			document.addEventListener("DOMContentLoaded", function () {

			let selectedProducts = [];

			// Initialize select2 for both base and secondary units
			$('#baseunit').select2({
				placeholder: '-- Select --',
				allowClear: true // This allows the placeholder to be shown even after an option is selected
			});

			$('#secondary_unit').select2({
				placeholder: '-- Select --',
				allowClear: true // This allows the placeholder to be shown even after an option is selected
			});

			// When the "Update" button is clicked
			document.getElementById('bulkUpdateBtn').addEventListener('click', function () {
				selectedProducts = [];

				document.querySelectorAll('.product-checkbox:checked').forEach(function (checkbox) {
					selectedProducts.push(checkbox.closest('.product-checkbox').getAttribute('value'));
				});

				console.log(selectedProducts);

				if (selectedProducts.length > 0) {
					document.getElementById("formContainers2").style.display = "block"; // Show modal if products are selected
				} else {
					alert('Please select at least one product.');
				}
			});

			$('#secondary_unit').on('change', function () {
				// console.log('vv');
				let conversionRateDiv = document.getElementById('conversion_rate');
				let baseUnitName = $('#baseunit option:selected').text(); 
				let secondaryUnitName = $('#secondary_unit option:selected').text(); 

				if (this.value){
					// console.log(secondaryUnitName)
					conversionRateDiv.style.display = 'block';
					document.getElementById('base_unit_name').textContent = baseUnitName;
					document.getElementById('secondary_unit_name').textContent = secondaryUnitName;
				} else {
					conversionRateDiv.style.display = 'none';
					document.getElementById('secondary_unit_name').textContent = '';
				}
			});

			// Handle form submission via AJAX
			document.getElementById('saveBtn').addEventListener('click', function (e) {
			e.preventDefault(); 

			// Use select2 method to get the selected value
			let selectedUnit = $('#baseunit').val(); 
			let secondaryUnit = $('#secondary_unit').val();
			let conversionRate = document.getElementById('conversion_value').value; 

			// console.log('Selected Unit:', selectedUnit);
			// console.log('Secondary Unit:', secondaryUnit);
			// console.log('Conversion Rate:', conversionRate);

			if (selectedProducts.length > 0 && selectedUnit && secondaryUnit && conversionRate) {
				// AJAX request to update products
				$.ajax({
					url: '<?= base_url("multiunit_update") ?>',
					method: 'POST',
					data: {
						products: selectedProducts, 
						base_unit: selectedUnit,  
						secondary_unit: secondaryUnit,  
						conversion_rate: conversionRate  
					},
					success: function (response) {
						alert('Update successful');
						document.getElementById("formContainers2").style.display = "none"; // Hide modal
						location.reload(); // Refresh page after success
					},
					error: function (xhr, status, error) {
						alert('Error occurred: ' + error);
					}
				});
			} else {
				alert('Please fill all required');
			}
		});


			// Close modal if Cancel button is clicked
			document.getElementById('cancel2').addEventListener('click', function () {
				document.getElementById("formContainers2").style.display = "none"; // Hide the modal
			});
			});





		</script>

        
        
        <?php include "footer.php" ?>

