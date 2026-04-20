<?php require_once "views/layout/header.php";?>


<div class="container">
	<div class="d-flex justify-content-center">
		<div class="card p-card">
			<div class="card-header">
				<h3>Login</h3>
				<div class="d-flex justify-content-end social_icon">
					<span><i class="fas fa-user"></i></span>
				</div>
			</div>
			<div class="card-body">
				<form id="form-login">
						<?php echo csrf_field(); ?>
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
					<div class="row align-items-center remember">
						<input type="checkbox" id="datos">Recordarme!
					</div>
					<div class="form-group">
						<input type="submit" value="Login" class="btn btn-block mt-3 login_btn">
					</div>
				</form>
			</div>
			<div class="card-footer">
				<div class="d-flex justify-content-center links">
					Aún no tienes cuenta?<a href="<?php echo e(BASE_URL); ?>/App/registro"> Registrarse</a>
				</div>
				<div class="d-flex justify-content-center">
					<a href="<?php echo e(BASE_URL); ?>/App/recover">¿Has olvidado la contraseña?</a>
				</div>
			</div>
		</div>
	</div>
</div>

<?php require_once "views/layout/footer.php"; ?>
