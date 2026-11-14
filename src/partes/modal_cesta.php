<!-- Modal que confirma que el disco se ha añadido a la cesta, con dos salidas: seguir comprando o ir a la cesta.
     Lo usan la tienda y las novedades del inicio; fichas.js lo abre -->
<div class="modal fade" id="modal-cesta" tabindex="-1" aria-labelledby="titulo-modal-cesta" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-cesta">Añadido a la cesta</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-modal-cesta" class="mb-0"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Seguir comprando</button>
        <a class="btn btn-primary" href="/cesta.php">Ir a la cesta</a>
      </div>
    </div>
  </div>
</div>
