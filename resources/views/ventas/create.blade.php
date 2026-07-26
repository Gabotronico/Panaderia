@extends('layouts.app')

@section('page-title', 'Nueva Venta')

@section('content')

{{-- Selector de almacén para administrador sin almacén asignado --}}
@if($almacenes)
<div class="alert alert-info mb-3">
    <form method="GET" action="{{ route('ventas.create') }}" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <label class="fw-bold mb-0 text-nowrap">
            <i class="fas fa-warehouse me-1"></i>Vender desde almacén:
        </label>
        <select name="almacen_id" class="form-select" style="max-width:280px" onchange="this.form.submit()">
            <option value="">— Seleccionar almacén —</option>
            @foreach($almacenes as $al)
                <option value="{{ $al->id }}" {{ request('almacen_id') == $al->id ? 'selected' : '' }}>
                    {{ $al->nombre }}
                </option>
            @endforeach
        </select>
    </form>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <!-- Productos Disponibles -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-box-open me-2"></i>Productos Disponibles
                @if($almacen)
                    <span class="badge bg-secondary ms-2">{{ $almacen->nombre }}</span>
                @endif
            </div>
            <div class="card-body">
                @if($productos->isEmpty())
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @if($almacenes && !$almacen)
                            Selecciona un almacén para ver los productos disponibles.
                        @else
                            No hay productos con stock disponible en este almacén.
                        @endif
                    </div>
                @else
                <!-- Buscador -->
                <div class="mb-3">
                    <input type="text"
                           class="form-control"
                           id="search-product"
                           placeholder="Buscar producto...">
                </div>

                <!-- Grid de Productos -->
                <div class="row" id="productos-grid">
                    @foreach($productos as $producto)
                        <div class="col-md-4 col-sm-6 mb-3 producto-item" data-nombre="{{ strtolower($producto->nombre) }}">
                            <div class="card h-100 producto-card" style="cursor: pointer;"
                                 onclick="agregarProducto({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->precio_venta }}, {{ $producto->stock_almacen }})">
                                <div class="card-body text-center">
                                    @if($producto->imagen)
                                        <img src="{{ asset('storage/' . $producto->imagen) }}"
                                             alt="{{ $producto->nombre }}"
                                             class="img-fluid mb-2 rounded"
                                             style="max-height: 100px; object-fit: cover;">
                                    @else
                                        <i class="fas fa-cookie-bite fa-3x text-muted mb-2"></i>
                                    @endif
                                    <h6 class="mb-1">{{ $producto->nombre }}</h6>
                                    <p class="mb-1 text-success"><strong>Bs {{ number_format($producto->precio_venta, 2) }}</strong></p>
                                    <small class="text-muted">Stock: {{ $producto->stock_almacen }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Carrito de Compra -->
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-shopping-cart me-2"></i>Carrito de Compra
                <span class="badge bg-light text-dark float-end" id="items-count">0</span>
            </div>
            <div class="card-body">
                <form action="{{ route('ventas.store') }}" method="POST" id="form-venta">
                    @csrf
                    @if($almacen && !Auth::user()->almacen_id)
                        <input type="hidden" name="almacen_id" value="{{ $almacen->id }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Número de Venta</label>
                        <input type="text" class="form-control" value="{{ $numeroVenta }}" readonly>
                    </div>
                    
                    <!-- Items del Carrito -->
                    <div id="cart-items" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted text-center">No hay productos en el carrito</p>
                    </div>
                    
                    <hr>
                    
                    <!-- Totales -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <strong id="subtotal">Bs0.00</strong>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descuento" class="form-label">Descuento:</label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="descuento" 
                                   name="descuento" 
                                   value="0" 
                                   step="0.01" 
                                   min="0"
                                   onchange="calcularTotal()">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Total:</h5>
                            <h4 class="mb-0 text-success" id="total">Bs0.00</h4>
                        </div>
                    </div>
                    
                    <!-- Tipo de Pago -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pago:</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_pago" id="pago_efectivo" value="efectivo" checked>
                                <label class="btn btn-outline-success w-100 py-2" for="pago_efectivo">
                                    <i class="fas fa-money-bill-wave d-block fs-4 mb-1"></i>
                                    <span class="fw-semibold">Efectivo</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo_pago" id="pago_qr" value="qr">
                                <label class="btn btn-outline-primary w-100 py-2" for="pago_qr">
                                    <i class="fas fa-qrcode d-block fs-4 mb-1"></i>
                                    <span class="fw-semibold">Pago QR</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Monto recibido (siempre visible) -->
                    <div class="mb-3">
                        <label for="monto_recibido" class="form-label" id="label-monto">Monto recibido del cliente:</label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number"
                                   class="form-control"
                                   id="monto_recibido"
                                   name="monto_recibido"
                                   step="0.50"
                                   min="0"
                                   placeholder="0.00"
                                   oninput="calcularCambio()">
                        </div>
                        <div id="cambio-display" class="mt-2 p-2 rounded d-none" style="background:#f0fdf4; border:1px solid #86efac;">
                            <div class="d-flex justify-content-between">
                                <span class="text-success fw-semibold">Cambio a devolver:</span>
                                <strong class="text-success" id="cambio-valor">Bs0.00</strong>
                            </div>
                        </div>
                        <div id="error-monto" class="text-danger small mt-1 d-none">
                            <i class="fas fa-exclamation-circle me-1"></i>El monto recibido es menor al total
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones:</label>
                        <textarea class="form-control"
                                  id="observaciones"
                                  name="observaciones"
                                  rows="2"></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="btn-finalizar" disabled>
                            <i class="fas fa-check me-2"></i>Finalizar Venta
                        </button>
                        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .producto-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .producto-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }
    .cart-item:last-child {
        border-bottom: none;
    }
</style>
@endpush

@push('scripts')
<script>
    let carrito = [];
    
    // Buscar productos
    document.getElementById('search-product').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.producto-item').forEach(item => {
            const nombre = item.dataset.nombre;
            if (nombre.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Agregar producto al carrito
    function agregarProducto(id, nombre, precio, stock) {
        const index = carrito.findIndex(item => item.id === id);
        
        if (index !== -1) {
            // Ya existe, incrementar cantidad
            if (carrito[index].cantidad < stock) {
                carrito[index].cantidad++;
            } else {
                alert('Stock insuficiente');
                return;
            }
        } else {
            // Agregar nuevo
            if (stock > 0) {
                carrito.push({
                    id: id,
                    nombre: nombre,
                    precio: precio,
                    cantidad: 1,
                    stock: stock
                });
            } else {
                alert('Producto sin stock');
                return;
            }
        }
        
        actualizarCarrito();
    }
    
    // Actualizar vista del carrito
    function actualizarCarrito() {
        const cartItemsDiv = document.getElementById('cart-items');
        const itemsCount = document.getElementById('items-count');
        const btnFinalizar = document.getElementById('btn-finalizar');
        
        if (carrito.length === 0) {
            cartItemsDiv.innerHTML = '<p class="text-muted text-center">No hay productos en el carrito</p>';
            itemsCount.textContent = '0';
            btnFinalizar.disabled = true;
        } else {
            cartItemsDiv.innerHTML = '';

            carrito.forEach((item, index) => {
                // Construir el DOM manualmente para evitar XSS via innerHTML con nombres de producto
                const div = document.createElement('div');
                div.className = 'cart-item';

                const headerRow = document.createElement('div');
                headerRow.className = 'd-flex justify-content-between align-items-center mb-2';

                const strong = document.createElement('strong');
                strong.textContent = item.nombre; // textContent evita XSS

                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.className = 'btn btn-danger btn-sm';
                btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
                btnEliminar.addEventListener('click', () => eliminarProducto(index));

                headerRow.appendChild(strong);
                headerRow.appendChild(btnEliminar);

                const controlRow = document.createElement('div');
                controlRow.className = 'd-flex justify-content-between align-items-center';

                const btnGroup = document.createElement('div');
                btnGroup.className = 'btn-group btn-group-sm';
                btnGroup.innerHTML = `
                    <button type="button" class="btn btn-outline-secondary" onclick="cambiarCantidad(${index}, -1)">-</button>
                    <input type="number" class="form-control form-control-sm text-center" style="width:60px" value="${item.cantidad}" readonly>
                    <button type="button" class="btn btn-outline-secondary" onclick="cambiarCantidad(${index}, 1)">+</button>
                `;

                const precio = document.createElement('span');
                precio.textContent = 'Bs' + (item.precio * item.cantidad).toFixed(2);

                controlRow.appendChild(btnGroup);
                controlRow.appendChild(precio);

                // Inputs ocultos con IDs y cantidades (no nombres — sin riesgo XSS)
                div.innerHTML += `
                    <input type="hidden" name="productos[${index}][id]" value="${item.id}">
                    <input type="hidden" name="productos[${index}][cantidad]" value="${item.cantidad}">
                `;

                div.prepend(controlRow);
                div.prepend(headerRow);
                cartItemsDiv.appendChild(div);
            });

            itemsCount.textContent = carrito.length;
            btnFinalizar.disabled = false;
        }
        
        calcularTotal();
    }
    
    // Cambiar cantidad
    function cambiarCantidad(index, cambio) {
        const nuevaCantidad = carrito[index].cantidad + cambio;
        
        if (nuevaCantidad > 0 && nuevaCantidad <= carrito[index].stock) {
            carrito[index].cantidad = nuevaCantidad;
            actualizarCarrito();
        } else if (nuevaCantidad > carrito[index].stock) {
            alert('Stock insuficiente');
        }
    }
    
    // Eliminar producto
    function eliminarProducto(index) {
        carrito.splice(index, 1);
        actualizarCarrito();
    }
    
    // Calcular totales
    function calcularTotal() {
        const subtotal  = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const descuento = parseFloat(document.getElementById('descuento').value) || 0;
        const total     = subtotal - descuento;

        document.getElementById('subtotal').textContent = 'Bs' + subtotal.toFixed(2);
        document.getElementById('total').textContent    = 'Bs' + total.toFixed(2);

        calcularCambio();
    }

    // Calcular cambio en tiempo real (solo para efectivo)
    function calcularCambio() {
        const subtotal      = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const descuento     = parseFloat(document.getElementById('descuento').value) || 0;
        const total         = subtotal - descuento;
        const montoRecibido = parseFloat(document.getElementById('monto_recibido').value) || 0;
        const tipoPago      = document.querySelector('input[name="tipo_pago"]:checked').value;
        const cambio        = montoRecibido - total;

        const cambioDisplay = document.getElementById('cambio-display');
        const cambioValor   = document.getElementById('cambio-valor');
        const errorMonto    = document.getElementById('error-monto');

        if (montoRecibido <= 0 || total <= 0) {
            cambioDisplay.classList.add('d-none');
            errorMonto.classList.add('d-none');
            return;
        }

        if (cambio < 0 && tipoPago === 'efectivo') {
            cambioDisplay.classList.add('d-none');
            errorMonto.classList.remove('d-none');
        } else {
            errorMonto.classList.add('d-none');
            // Mostrar cambio solo en efectivo
            if (tipoPago === 'efectivo' && cambio >= 0) {
                cambioDisplay.classList.remove('d-none');
                cambioValor.textContent = 'Bs' + cambio.toFixed(2);
            } else {
                cambioDisplay.classList.add('d-none');
            }
        }
    }

    // Cambiar etiqueta del campo según tipo de pago
    document.querySelectorAll('input[name="tipo_pago"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const label = document.getElementById('label-monto');
            label.textContent = this.value === 'efectivo'
                ? 'Monto recibido del cliente:'
                : 'Monto recibido (QR):';
            // Limpiar campo y ocultar cambio/error al cambiar método
            document.getElementById('monto_recibido').value = '';
            document.getElementById('cambio-display').classList.add('d-none');
            document.getElementById('error-monto').classList.add('d-none');
        });
    });

    // Validar formulario
    document.getElementById('form-venta').addEventListener('submit', function(e) {
        if (carrito.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto al carrito');
            return;
        }

        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;
        const subtotal = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const descuento = parseFloat(document.getElementById('descuento').value) || 0;
        const total     = subtotal - descuento;
        const recibido  = parseFloat(document.getElementById('monto_recibido').value) || 0;

        if (recibido <= 0) {
            e.preventDefault();
            document.getElementById('monto_recibido').focus();
            return;
        }

        if (tipoPago === 'efectivo' && recibido < total) {
            e.preventDefault();
            document.getElementById('error-monto').classList.remove('d-none');
            document.getElementById('monto_recibido').focus();
        }
    });
</script>
@endpush