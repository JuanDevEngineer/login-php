<form id="form-filtros">
    <?= $csrf->field() ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="filtro-usuario">Usuario</label>
                <select name="usuario" id="filtro-usuario" class="form-control">
                    <option value="">Todos</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="filtro-estado">Estado</label>
                <select name="estado" id="filtro-estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-dark btn-block">Buscar</button>
</form>
