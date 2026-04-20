<?php require_once "views/layout/header.php"; ?>


    <div class="container">
        <div class="col-md-6 offset-md-3">
            <div class="" style="height: 220px; margin-top: 170px; background-color: rgba(0,0,0,0.5) ">
                <div class="card-header">
                    <h3 class="text-center">Nueva Contraseña</h3>
                </div>
                <form id="form-password">

                    <input type="hidden" name="id_user" id="id_user" value="<?php echo e($user_id['id_usuario'] ?? ''); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <div class="input-group form-group col-md-10 offset-md-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                        </div>
                        <input type="password" name="pass_recover" id="pass_recover" class="form-control" placeholder="nueva password">
                    </div>

                    <div class="input-group form-group col-md-10 offset-md-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                        </div>
                        <input type="password" name="pass_recover_confirm" id="pass_recover_confirm" class="form-control" placeholder="confirmar password">
                    </div>

                    <div class="form-group col-md-10 offset-md-1">
                        <input type="submit" value="Cambiar" class="btn btn-block mt-3 login_btn">
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once "views/layout/footer.php"; ?>
