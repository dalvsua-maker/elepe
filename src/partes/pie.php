  </main>

  <!-- Pie de página: la tienda, sus datos de contacto, el menú y las redes sociales -->
  <footer class="pie bg-body-tertiary border-top mt-5">
    <div class="container-fluid px-3 px-lg-5 py-5">
      <div class="row g-4">
        <!-- Logo y presentación -->
        <div class="col-lg-4">
          <a href="/" class="d-inline-block mb-3">
            <img src="/img/logo.svg" alt="Elepé" width="110" height="40">
          </a>
          <p class="text-secondary mb-0">Discos y vinilos en el centro de Vigo desde 1979.</p>
        </div>

        <!-- Dirección y teléfono de la tienda física -->
        <div class="col-sm-6 col-lg-3">
          <h2 class="pie-titulo">Visítanos</h2>
          <address class="mb-0">
            <a class="pie-enlace" href="https://www.google.com/maps/search/?api=1&amp;query=Elep%C3%A9%20R%C3%BAa%20do%20Doutor%20Cadaval%2037%20Vigo" target="_blank" rel="noopener">
              Rúa do Doutor Cadaval, 37<br>
              36202 Vigo, Pontevedra<span class="visually-hidden"> (abre Google Maps en otra pestaña)</span>
            </a>
            <br>
            <a class="pie-enlace d-inline-block mt-2" href="tel:+34986227614">986 22 76 14</a>
          </address>
        </div>

        <!-- Páginas de la tienda online -->
        <div class="col-6 col-sm-3 col-lg-2">
          <h2 class="pie-titulo">Navega</h2>
          <ul class="list-unstyled mb-0">
            <li><a class="pie-enlace" href="/">Inicio</a></li>
            <li><a class="pie-enlace" href="/tienda.php">Tienda</a></li>
            <li><a class="pie-enlace" href="/contacto.php">Contacto</a></li>
          </ul>
        </div>

        <!-- Redes sociales (enlaces de texto, se abren en otra pestaña) -->
        <div class="col-6 col-sm-3 col-lg-3">
          <h2 class="pie-titulo">Síguenos</h2>
          <ul class="list-unstyled mb-0">
            <li>
              <a class="pie-enlace" href="https://www.instagram.com/discos_elepe/" target="_blank" rel="noopener">
                Instagram<span class="visually-hidden"> (se abre en otra pestaña)</span>
              </a>
            </li>
            <li>
              <a class="pie-enlace" href="https://www.facebook.com/p/Discos-Elepe-100063058729145/" target="_blank" rel="noopener">
                Facebook<span class="visually-hidden"> (se abre en otra pestaña)</span>
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Línea final -->
      <div class="border-top mt-5 pt-4 small text-secondary">
        <span>© <?= date('Y') ?> Elepé · Discos y vinilos</span>
      </div>
    </div>
  </footer>
</body>
</html>