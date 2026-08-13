<form id="form-avatar" enctype="multipart/form-data" method="post"
      action="<?= e($baseUrl) ?>/api/profile/image">
    <?= $csrf->field() ?>
    <input type="hidden" name="id" value="<?= e($authUser->id) ?>">

    <div class="input-group mb-0">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-camera-retro"></i></span>
        </div>
        <div class="custom-file">
            <input type="file" name="profile" class="custom-file-input" id="avatar-file"
                   accept="image/png,image/jpeg,image/gif,image/webp" required>
            <label class="custom-file-label" for="avatar-file">Elegir imagen</label>
        </div>
        <div class="input-group-append">
            <button class="btn btn-dark" type="submit">Subir</button>
        </div>
    </div>
</form>
