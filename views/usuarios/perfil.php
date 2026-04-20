<!-- Navbar -->
<?php require_once 'views/dashboard/header.admin.php' ?>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<?php require_once 'views/dashboard/sidebar.admin.php' ?>

<!-- Content Wrapper. Contains page content -->
<?php require_once 'views/dashboard/container.admin.php' ?>
<!-- /.content-wrapper -->

<!-- Main content -->
<!-- <section class="content"> -->

<!-- <div class="container-fluid"> -->
<div class="row">
    <div class="col-4">
        <!-- Default box -->
        <div class="card">
            <div class="card-body">
                <div class="usuario-perfil">
                    <div>
                        <?php if (isset($_SESSION['image'])) : ?>
                            <img src="<?php echo e($_SESSION['image']); ?>" class="img-circle" />
                        <?php else : ?>
                            <img src="<?php echo e(BASE_URL); ?>/assets/uploads/29292.jpg" class="img-circle" />
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="modal-footer">
                <form id="subirimagen" enctype="multipart/form-data">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-camera-retro"></i></span>
                        </div>
                        <div class="custom-file">
                            <input type="file" name="image-rol" class="custom-file-input" id="image-rol">
                            <label class="custom-file-label" for="inputGroupFile01">Imagen</label>
                        </div>
                        <input class="btn btn-dark" type="submit" value="." />
                    </div>
                </form>
            </div>
            <!-- card-footer -->
        </div>
        <!-- /.card -->
    </div>

    <div class="col-8">
        <!-- Default box -->
        <div class="card">

            <div class="card-body">
                
            <div class="usuario-nombres">
                <div>
                    <h3>Datos</h3>
                    <form id="form-login">
                        <div class="row">
                            <div class="input-group form-group col-6">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>

                                <input type="hidden" name="id_k" id="id_k" value="<?php echo e($_SESSION['id_k'] ?? ''); ?>" />

                                <input type="text" name="usuario" id="usuario" class="form-control" placeholder="usuario" value="<?php echo e($_SESSION['usuario'] ?? ''); ?>" disabled="true" />

                            </div>
                            <div class="input-group form-group col-6">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-atom"></i></span>
                                </div>
                                <input type="text" name="email" id="email" class="form-control" placeholder="email" value="<?php echo e($_SESSION['email'] ?? ''); ?>" disabled="true" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /.card-body
            <div class="card-footer">
                Footer
            </div>
            /.card-footer -->
        </div>
        <!-- /.card -->
    </div>

    <div class="col-12">
        <!-- Default box -->
        <div class="card">

            <div class="card-body">
                
            <div class="usuario-nombres">
                <div>
                    <h3>Cambiar Contraseña</h3>
                    <form id="form-login">
                        <div class="row">
                            <div class="input-group form-group col-md-12">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                </div>
                                <input type="text" name="" id="" class="form-control" placeholder="Nueva contraseña" />
                            </div>
                            <div class="form-group col-md-12 text-center">
                                <input type="submit" class="btn btn-dark" name="usuario" id="usuario" value="Actualizar"/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /.card-body
            <div class="card-footer">
                Footer
            </div>
            /.card-footer -->
        </div>
        <!-- /.card -->
    </div>
</div>
<!-- </div> -->
<!-- </section> -->
<!-- /.content -->

<!-- Footer -->
<?php require_once 'views/dashboard/footer.admin.php' ?>