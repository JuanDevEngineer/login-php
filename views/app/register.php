
    <!--<div class="container">
        <h1 class="text-center my-3">Registrar</h1>
        <div class="row justify-content-md-center">
            <div class="card p-5">
                <form id="form-registro">
                    <div class="form-group">
                        <label for="">Nombre</label>
                        <input type="text" name="name" id="name" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label for="">Contraseña</label>
                        <input type="text" name="pass" id="pass" class="form-control" />
                    </div>
                    <input type="submit" class="btn btn-dark btn-block" value="Enviar" />
                </form>
            </div>
        </div>
	</div>-->
	
<?php require_once "views/layout/header.php";?>

    <div class="container">
		<div class="d-flex justify-content-center h-100">
			<div class="card">
				<div class="card-header">
					<h3>Registrase</h3>
				</div>
				<div class="card-body">
					<form id="form-registro">
							<?php echo csrf_field(); ?>
						<div class="input-group form-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-envelope"></i></span>
							</div>
							<input type="email" name="correo" id="correo" class="form-control" placeholder="correo">
						</div>
						<div class="input-group form-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-user"></i></span>
							</div>
							<input type="text" name="name" id="name" class="form-control" placeholder="usuario">
						</div>
						<div class="input-group form-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-key"></i></span>
							</div>
							<input type="password" name="pass" id="pass" class="form-control" placeholder="password">
						</div>
						<div class="form-group">
							<input type="submit" value="Registrar" class="btn btn-block mt-3 login_btn">
						</div>
						
					</form>
				</div>
				<div class="card-footer">
					<div class="d-flex justify-content-center">
						<a href="<?php echo e(BASE_URL); ?>/App/acceso">Volver al login</a>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php require_once "views/layout/footer.php"; ?>