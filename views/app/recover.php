<?php require_once "views/layout/header.php"; ?>


<div class="container">
    <div class="col-md-6 offset-md-3">
        <div class="" style="height: 220px; margin-top: 170px; background-color: rgba(0,0,0,0.5) ">
            <div class="card-header">
                <h3 class="text-center">Recuperar contraseña</h3>
            </div>
            <form id="form-recover">
                <?php echo csrf_field(); ?>
                <div class="input-group form-group col-md-10 offset-md-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="email" name="email_recover" id="email_recover" class="form-control" placeholder="correo electronico">

                </div>

                <div class="form-group col-md-10 offset-md-1">
                    <input type="submit" value="Enviar" class="btn btn-block mt-3 login_btn">
                </div>
            </form>
            <div class="card-footer">
                <div class="d-flex justify-content-center links">
                    <a href="<?php echo e(BASE_URL); ?>/App/acceso">Volver al login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "views/layout/footer.php"; ?>