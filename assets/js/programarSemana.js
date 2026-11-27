
// Variables globales para compatibilidad con el sistema de guardado
var Peso1, projectCode1, codPdre1, idPadreOT1, Proceso1;

// Configurar funcionalidad del popup de imagen
function initializeImagePopup() {
    var showImageBtn = document.getElementById('showImageBtn');
    var imagePopup = document.getElementById('imagePopup');
    var closePopup = document.getElementById('closePopup');
    var zoomableImage = document.getElementById('zoomableImage');
    var zoomInBtn = document.getElementById('zoomIn');
    var zoomOutBtn = document.getElementById('zoomOut');
    var resetZoomBtn = document.getElementById('resetZoom');
    
    let currentZoom = 1;
    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;

    // Verificar que los elementos existen
    if (!showImageBtn || !imagePopup || !closePopup || !zoomableImage) {
        return;
    }

    console.log('Elementos del popup encontrados, configurando eventos...');
    
    // IMPORTANTE: Asegurar que el popup esté oculto al inicio
    imagePopup.classList.remove('active');
    
    // Mostrar la imagen emergente al presionar el botón
    showImageBtn.onclick = function() {
        console.log('Botón clicked - mostrando popup');
        
        // Agregar la clase active para mostrar el popup
        imagePopup.classList.add('active');
        console.log('Clase active agregada');
        
        // Resetear zoom al abrir
        currentZoom = 1;
        zoomableImage.style.transform = 'scale(1)';
        zoomableImage.style.transformOrigin = 'center center';
    }

    // Cerrar la imagen emergente al presionar la "X"
    closePopup.onclick = function() {
        console.log('Cerrando popup');
        imagePopup.classList.remove('active');
        // Resetear zoom al cerrar
        currentZoom = 1;
        zoomableImage.style.transform = 'scale(1)';
    }

    // Cerrar la imagen emergente si se hace clic fuera de la imagen
    window.onclick = function(event) {
        if (event.target == imagePopup) {
            console.log('Click fuera del popup - cerrando');
            imagePopup.classList.remove('active');
            // Resetear zoom al cerrar
            currentZoom = 1;
            zoomableImage.style.transform = 'scale(1)';
        }
    }
        
        // =============================================================================
        // FUNCIONALIDAD DE ZOOM
        // =============================================================================
        
        // Zoom In
        if (zoomInBtn) {
            zoomInBtn.onclick = function(e) {
                e.stopPropagation();
                currentZoom = Math.min(currentZoom + 0.3, 5); // Máximo 5x para ver más detalles
                zoomableImage.style.transform = `scale(${currentZoom})`;
                zoomableImage.style.transformOrigin = 'center center';
                
                // Si la imagen es muy grande, permitir scroll
                if (currentZoom > 2) {
                    imagePopup.style.overflow = 'auto';
                }
            }
        }
        
        // Zoom Out
        if (zoomOutBtn) {
            zoomOutBtn.onclick = function(e) {
                e.stopPropagation();
                currentZoom = Math.max(currentZoom - 0.3, 0.3); // Mínimo 0.3x
                zoomableImage.style.transform = `scale(${currentZoom})`;
                zoomableImage.style.transformOrigin = 'center center';
                
                // Si volvemos a tamaño normal, centrar
                if (currentZoom <= 1) {
                    imagePopup.style.overflow = 'auto';
                    imagePopup.scrollTop = 0;
                    imagePopup.scrollLeft = 0;
                }
            }
        }
        
        // Reset Zoom
        if (resetZoomBtn) {
            resetZoomBtn.onclick = function(e) {
                e.stopPropagation();
                currentZoom = 1;
                zoomableImage.style.transform = 'scale(1)';
                zoomableImage.style.transformOrigin = 'center center';
                imagePopup.style.overflow = 'auto';
                imagePopup.scrollTop = 0;
                imagePopup.scrollLeft = 0;
            }
        }
        
        // Zoom con rueda del mouse
        if (zoomableImage) {
            zoomableImage.addEventListener('wheel', function(e) {
                e.preventDefault();
                
                if (e.deltaY < 0) {
                    // Scroll hacia arriba = zoom in
                    currentZoom = Math.min(currentZoom + 0.1, 3);
                } else {
                    // Scroll hacia abajo = zoom out
                    currentZoom = Math.max(currentZoom - 0.1, 0.5);
                }
                
                zoomableImage.style.transform = `scale(${currentZoom})`;
            });
            
            // Arrastrar imagen cuando está ampliada
            zoomableImage.addEventListener('mousedown', function(e) {
                if (currentZoom > 1) {
                    isDragging = true;
                    startX = e.pageX - zoomableImage.offsetLeft;
                    startY = e.pageY - zoomableImage.offsetTop;
                    zoomableImage.style.cursor = 'grabbing';
                }
            });
            
            document.addEventListener('mousemove', function(e) {
                if (isDragging && currentZoom > 1) {
                    e.preventDefault();
                    var x = e.pageX - startX;
                    var y = e.pageY - startY;
                    zoomableImage.style.transform = `scale(${currentZoom}) translate(${x}px, ${y}px)`;
                }
            });
            
            document.addEventListener('mouseup', function() {
                isDragging = false;
                if (zoomableImage) {
                    zoomableImage.style.cursor = 'grab';
                }
            });
        }
        
    }

// ===============================
// FUNCIONALIDAD DEL DIV FLOTANTE
// ===============================
function initializeFloatingDiv() {
    const div = document.getElementById('div-flotante');
    const closeBtn = document.getElementById('close-btn');
    const increaseSizeBtn = document.getElementById('increase-size-btn');
    
    let isMaximized = false;
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;
    // Funcionalidad del botón de cerrar
    if (closeBtn && div) {
        closeBtn.addEventListener('click', function() {
          
            div.style.display = 'none';
            
            // Restablecer el tamaño del div flotante al tamaño original
            $(div).css({
                'width': '60%',
                'height': '70%'
            });
            
            // Reiniciar el total de avance
            if (typeof window.totalAvance !== 'undefined') {
                window.totalAvance = 0;
            }
            
            // Aplicar colores después de cerrar
            if (typeof aplicarColoresEnFilas === 'function') aplicarColoresEnFilas();
            if (typeof aplicarColoresEnBORDES === 'function') aplicarColoresEnBORDES();
        });
    }

    // Funcionalidad del botón de agrandar
    if (increaseSizeBtn && div) {
        increaseSizeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
       
            if (!isMaximized) {
                div.style.width = '95vw';
                div.style.height = '90vh';
                div.style.left = '50%';
                div.style.top = '50%';
                div.style.transform = 'translate(-50%, -50%)';
                div.style.resize = 'none';
                isMaximized = true;
            } else {
                div.style.width = '';
                div.style.height = '';
                div.style.left = '';
                div.style.top = '';
                div.style.transform = '';
                div.style.resize = '';
                isMaximized = false;
            }
        });
    }

    // Hacer que el div sea movible arrastrando desde cualquier parte libre
    if (div) {
        function onPointerMove(e) {
            if (!isDragging) return;
            let clientX = e.clientX;
            let clientY = e.clientY;
            div.style.left = (clientX - offsetX) + 'px';
            div.style.top = (clientY - offsetY) + 'px';
            div.style.position = 'fixed';
        }

        function onPointerUp() {
            if (isDragging) {
                isDragging = false;
                document.body.style.userSelect = '';
                window.removeEventListener('pointermove', onPointerMove);
                window.removeEventListener('pointerup', onPointerUp);
                console.log('Fin de arrastre');
            }
        }

        // Reemplazamos el arrastre genérico: sólo el título (.flotante-title) puede iniciar drag
        div.addEventListener('pointerdown', function(e) {
            const header = div.querySelector('.flotante-title');
            // Abort if no header yet or click not on header
            if(!header || (e.target !== header && !header.contains(e.target))) {
                return; // Click dentro del contenido, no arrastrar
            }
            if (isMaximized) return; // No arrastrar si está maximizado

            console.log('Iniciando arrastre (HEADER ONLY) desde:', e.target.tagName);
            const rect = div.getBoundingClientRect();
            div.style.width = rect.width + 'px';
            div.style.height = rect.height + 'px';
            if (!div.style.left) div.style.left = rect.left + 'px';
            if (!div.style.top) div.style.top = rect.top + 'px';
            div.style.transform = '';
            isDragging = true;
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            document.body.style.userSelect = 'none';
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
        });
    }
    
    // Inicializar eventos adicionales del div flotante
    initializeFloatingDivEvents();
}

// Función para calcular la suma total de porcentajes de AvanceReal (avance físico) en la tabla del div flotante
function calcularSumaAvanceReal() {
    var suma = 0;
    var filas = $('#div-flotante .tabla-dinamica tbody tr');
    
    filas.each(function() {
        var $row = $(this);
        var $tds = $row.find('td');
        
        // Ignorar filas placeholder (con colspan o mensaje "No se encontraron")
        if ($tds.length === 1) {
            var tdText = $tds.text().trim();
            var colspan = $tds.attr('colspan');
            if ((colspan && parseInt(colspan) > 1) || tdText.toLowerCase().includes('no se encontraron')) {
                return; // continuar al siguiente tr
            }
        }
        
        var avanceReal = 0;
        
        // Buscar el campo AvanceReal con data-field
        var $cellAvanceReal = $row.find('td[data-field="AvanceReal"]');
        if ($cellAvanceReal.length > 0) {
            var $input = $cellAvanceReal.find('input');
            if ($input.length > 0) {
                avanceReal = parseFloat($input.val()) || 0;
            } else {
                var txtAvReal = $cellAvanceReal.text() || '';
                txtAvReal = txtAvReal.replace(/[^0-9\.\-]/g, '');
                avanceReal = parseFloat(txtAvReal) || 0;
            }
        }
        
        // Normalizar y sumar
        avanceReal = Math.max(0, Math.min(100, avanceReal));
        suma += avanceReal;
    });
    
    console.log('Suma total de AvanceReal (físico) calculada:', suma);
    return suma;
}

// Hacer la función disponible globalmente
window.calcularSumaAvanceReal = calcularSumaAvanceReal;

// Función para inicializar eventos adicionales del div flotante
function initializeFloatingDivEvents() {
    // Solo conservamos los eventos básicos aquí, los eventos de edición están en initializeDivFlotanteEditableEvents
    
    $(document).on('click', '.cerrar-row-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var estado = $row.find('[data-field="Estado"]').text();
        var idPrograma = $row.find('[data-field="IdPrograma"]').text();
        
        if (estado === '1') {
            // Contar cuántas semanas abiertas (Estado = 1 o filas nuevas sin Estado) hay en la tabla
            var semanasAbiertas = 0;
            $('#div-flotante .tabla-dinamica tbody tr').each(function() {
                var $tr = $(this);
                
                // Ignorar filas placeholder (con colspan o mensaje "No se encontraron")
                var $tds = $tr.find('td');
                if ($tds.length === 1) {
                    var tdText = $tds.text().trim();
                    var colspan = $tds.attr('colspan');
                    if ((colspan && parseInt(colspan) > 1) || tdText.toLowerCase().includes('no se encontraron')) {
                        return; // continuar al siguiente tr
                    }
                }
                
                var $estadoField = $tr.find('[data-field="Estado"]');
                var estadoFila = $estadoField.length > 0 ? $estadoField.text().trim() : '';
                
                // Una fila se considera "abierta" si:
                // - Tiene Estado = '1'
                // - O es una fila NUEVA (no tiene campo Estado, ya que no se ha guardado)
                if (estadoFila === '1' || estadoFila === '') {
                    semanasAbiertas++;
                }
            });
            
            console.log('Semanas abiertas actualmente (incluyendo nuevas):', semanasAbiertas);
            
            // VALIDACIÓN: Solo exigir 100% si esta es la ÚLTIMA semana abierta
            // Si hay más de una semana abierta, se puede cerrar sin restricción
            if (semanasAbiertas <= 1) {
                var sumaAvanceReal = calcularSumaAvanceReal();
                var tolerance = 0.01;
                
                if (Math.abs(sumaAvanceReal - 100) > tolerance) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se puede cerrar la última semana',
                        html: 'La suma de los porcentajes de <strong>Avance Físico (Real)</strong> debe ser <strong>100%</strong> para cerrar la última semana.<br><br>' +
                              'Suma actual: <strong>' + sumaAvanceReal.toFixed(2) + '%</strong><br><br>' +
                              'Por favor, ajusta los porcentajes de avance físico antes de cerrar.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
            }
            
            // Cambiar a cerrado
            Swal.fire({
                title: '¿Cerrar semana?',
                text: '¿Estás seguro de que quieres cerrar esta semana?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'consultasAjax/actualizar_estado.php',
                        method: 'POST',
                        data: {
                            idPrograma: idPrograma,
                            estado: 2
                        },
                        dataType: 'json',
                        success: function(response) {
                        if (response.success) {
                            $btn.text('Cerrado').css('background-color', '#95a5a6');
                            $row.find('[data-field="Estado"]').text('2');
                            
                            // Remover de abrirSemanas si estaba ahí
                            var index = window.abrirSemanas.indexOf(idPrograma);
                            if (index > -1) {
                                window.abrirSemanas.splice(index, 1);
                            }

                            // Mostrar advertencias si las hubiere (no bloqueantes)
                            if (response.warnings && Array.isArray(response.warnings) && response.warnings.length > 0) {
                                var html = '';
                                response.warnings.forEach(function(w, i) {
                                    html += '<div style="text-align:left;margin-bottom:8px;">' + (i+1) + '. ' + (w.message || '') + '</div>';
                                });
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Advertencias',
                                    html: html,
                                    width: 600
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al cerrar semana',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de comunicación',
                            text: 'Error de comunicación al cerrar semana'
                        });
                    }
                    });
                }
            });
        } else if (estado === '2') {
            // Agregar a la lista de semanas para abrir
            if (!window.abrirSemanas.includes(idPrograma)) {
                window.abrirSemanas.push(idPrograma);
                $btn.css('background-color', '#ff6b6b'); // Color para indicar selección
            } else {
                // Remover de la lista si ya estaba seleccionada
                var index = window.abrirSemanas.indexOf(idPrograma);
                if (index > -1) {
                    window.abrirSemanas.splice(index, 1);
                    $btn.css('background-color', '#95a5a6'); // Volver al color original
                }
            }
        }
    });
}

// ===============================
// FUNCIONES PARA EL DIV FLOTANTE
// ===============================

// Función para posicionar el div flotante donde se hizo clic derecho
// Posicionamiento estable del div flotante: se centra la primera vez y ya no "salta" con clics posteriores
function setDivFlotantePosition(e) {
    const div = document.getElementById('div-flotante');
    if (!div) return;
    // Solo posicionar si aún no está visible (evita salto al primer click dentro)
    const isHidden = (div.style.display === 'none') || (getComputedStyle(div).display === 'none');
    if (!isHidden) return; // ya visible, no mover
    // Centrar en ventana una única vez
    div.style.position = 'fixed';
    div.style.left = '50%';
    div.style.top = '50%';
    div.style.transform = 'translate(-50%, -50%)';
    div.dataset.fixedPosition = 'true';
}

// Función para insertar títulos en la barra del div flotante
function insertHeaderTitles(headers) {
    // O crear dinámicamente una barra de título
    var div = document.getElementById('div-flotante');
    if (div && headers && headers.length) {
        // Buscar si ya existe un título
        var existingTitle = div.querySelector('.flotante-title');
        if (existingTitle) {
            existingTitle.textContent = headers.join(' - ');
        } else {
            var titleDiv = document.createElement('div');
            titleDiv.className = 'flotante-title';
            titleDiv.style.cssText = 'background: #1976d2; color: white; padding: 10px; font-weight: bold; margin-bottom: 10px; border-radius: 5px;';
            titleDiv.textContent = headers.join(' - ');
            div.insertBefore(titleDiv, div.firstChild);
        }}}
// Ejecutar cuando el DOM esté listo
$(document).ready(function() {
    initializeImagePopup();
    initializeFloatingDiv();
    initializeFloatingDivEvents();
    initializeFloatingDivButtons();
    initializeEmailButtons();
    initializeMainTable();
    initializeDoubleClickEvents();
    initializeEditableFieldEvents();
    initializeAdditionalButtons();
    initializeDivFlotanteEditableEvents();
    restrictFloatingDivDrag();
});

// Fallback para asegurar que se ejecute
document.addEventListener('DOMContentLoaded', function() {
    initializeImagePopup();
    initializeFloatingDiv();
});

// Hacer las funciones disponibles globalmente
window.setDivFlotantePosition = setDivFlotantePosition;
window.insertHeaderTitles = insertHeaderTitles;

// Restringir el arrastre sólo a la barra/título y anular cualquier onmousedown previo que afecta todo el div
function restrictFloatingDivDrag(){
    const div = document.getElementById('div-flotante');
    if(!div) return;
    // Anular handlers globales previos (de divMovible_*.js) que permiten arrastrar desde cualquier parte
    div.onmousedown = null;
    div.style.cursor = 'default';
    let header = div.querySelector('.flotante-title');
    if(!header){
        // Si aún no se creó el título, esperar y reintentar brevemente
        setTimeout(restrictFloatingDivDrag, 400);
        return;
    }
    header.style.cursor = 'move';
    let dragging = false, offsetX = 0, offsetY = 0;
    function onMove(e){
        if(!dragging) return;
        const vw = window.innerWidth, vh = window.innerHeight;
        let left = e.clientX - offsetX, top = e.clientY - offsetY;
        // Limitar a la ventana
        const rect = div.getBoundingClientRect();
        const w = rect.width, h = rect.height;
        if(left < 0) left = 0; if(top < 0) top = 0;
        if(left + w > vw) left = vw - w; if(top + h > vh) top = vh - h;
        div.style.left = left + 'px';
        div.style.top = top + 'px';
        div.style.transform = ''; // asegurar arrastre real, remover centrado
    }
    function onUp(){
        if(dragging){ dragging = false; document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); document.body.style.userSelect=''; }
    }
    header.addEventListener('mousedown', function(e){
        e.preventDefault();
        dragging = true;
        const rect = div.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        document.body.style.userSelect='none';
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

// ===============================
// FUNCIONES DE TABLAS Y COLORES - MIGRADAS DESDE PHP
// ===============================

// Función para obtener el avance de una celda específica
function obtenerAvance(rowData, columnName, callback) {
    var codPdre = rowData.CodPdre;
    var idPadreOT = rowData.IdPadreOT;

    $.ajax({
        url: 'consultasAjax1/consulta2_1.php',
        method: 'POST',
        data: {
            idPadreOT: idPadreOT,
            codPdre: codPdre,
            proceso: columnName
        },
        success: function(response) {
            var jsonResponse = JSON.parse(response);
            var total = parseFloat(jsonResponse.total);
            callback(total);
        },
        error: function() {
            callback(0);
        }
    });
}

// Función para aplicar colores basados en el porcentaje de avance
function aplicarColorAvance(cell, total) {
    if (total >= 100) {
        $(cell).css('background-color', '#90EE90');  // Verde claro (LightGreen)
    } else if (total >= 75) {
        $(cell).css('background-color', '#FFFF99');  // Amarillo claro (LightYellow)
    } else {
        $(cell).css('background-color', '#FFFFFF');  // Blanco
    }
}

// Función para aplicar colores en todas las filas de la tabla
function aplicarColoresEnFilas() {
    if (typeof window.subQueryTable !== 'undefined' && window.subQueryTable) {
        window.subQueryTable.rows().every(function(rowIdx) {
            var rowData = this.data();
            var rowNode = this.node();
            
            // Iterar sobre todas las columnas definidas (no solo las visibles)
            window.subQueryTable.columns().every(function(colIdx) {
                var columnDef = window.subQueryTable.settings()[0].aoColumns[colIdx];
                var columnName = columnDef.mData || columnDef.data;
                
                // Solo procesar columnas MO (procesos)
                if (columnName && columnName.startsWith('MO')) {
                    // Obtener la celda usando DataTables (funciona con columnas ocultas)
                    var cell = window.subQueryTable.cell(rowIdx, colIdx).node();
                    if (cell) {
                        obtenerAvance(rowData, columnName, function(total) {
                            aplicarColorAvance(cell, total);
                        });
                    }
                }
            });
        });
    }
}

// Función para obtener el estado programado
function obtenerEstadoProgramado(rowData, columnName, callback) {
    var codPdre = rowData.CodPdre;
    var idPadreOT = rowData.IdPadreOT;

    $.ajax({
        url: 'consultasAjax1/consulta2_2.php',
        method: 'POST',
        data: {
            idPadreOT: idPadreOT,
            codPdre: codPdre,
            proceso: columnName
        },
        success: function(response) {
            var jsonResponse = JSON.parse(response);
            var RESULT = parseFloat(jsonResponse.RESULT);
            callback(RESULT);
        },
        error: function() {
   
            callback(0);
        }
    });
}

// Función para aplicar bordes basados en el estado
function aplicarBordeCell(cell, RESULT) {
    if (RESULT == 2) {
        $(cell).css('border', '2px solid #f7071f');  // Borde rojo
    } else {
        $(cell).css('border', '2px solid #ffffff');  // Borde blanco
    }
}

// Función para aplicar colores en bordes
function aplicarColoresEnBORDES() {
    if (typeof window.subQueryTable !== 'undefined' && window.subQueryTable) {
        window.subQueryTable.rows().every(function(rowIdx) {
            var rowData = this.data();
            
            // Iterar sobre todas las columnas definidas (no solo las visibles)
            window.subQueryTable.columns().every(function(colIdx) {
                var columnDef = window.subQueryTable.settings()[0].aoColumns[colIdx];
                var columnName = columnDef.mData || columnDef.data;
                
                // Solo procesar columnas MO y SUT
                if (columnName && (columnName.startsWith('MO') || columnName === 'SUT')) {
                    var cell = window.subQueryTable.cell(rowIdx, colIdx).node();
                    if (cell) {
                        obtenerEstadoProgramado(rowData, columnName, function(RESULT) {
                            aplicarBordeCell(cell, RESULT);
                        });
                    }
                }
            });
        });
    }
}

// Hacer las funciones disponibles globalmente
window.obtenerAvance = obtenerAvance;
window.aplicarColorAvance = aplicarColorAvance;
window.aplicarColoresEnFilas = aplicarColoresEnFilas;
window.obtenerEstadoProgramado = obtenerEstadoProgramado;
window.aplicarBordeCell = aplicarBordeCell;
window.aplicarColoresEnBORDES = aplicarColoresEnBORDES;

// ===============================
// FUNCIONES DE BOTONES Y EVENTOS - MIGRADAS DESDE PHP
// ===============================

// Función para inicializar los botones de correo
function initializeEmailButtons() {
    $(document).on('click', '.btn-correo', function () {
        let id = $(this).data('id');
        // Confirmación
        Swal.fire({
            title: '¿Enviar correo?',
            text: '¿Deseas enviar un correo con esta información?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'enviarCorreo_Semana.php',
                    method: 'POST',
                    data: { id: id },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Correo enviado',
                            text: 'Correo enviado correctamente.'
                        });
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al enviar el correo.'
                        });
                    }
                });
            }
        });
    });
}

// Función para inicializar la tabla principal
function initializeMainTable() {
    // Crear la tabla principal si no existe
    if (!$.fn.DataTable.isDataTable('#example')) {
        window.mainTable = $("#example").DataTable({
            searching: false, 
            paging: false, 
            lengthChange: false, 
            info: false, 
            ordering: true,
            dom: 'Bfrtip',
            ajax: { 
                url: "consultasAjax1/consulta.php", 
                method: "POST", 
                dataSrc: "" 
            },
            columns: [
                { "data": "IdPadre" }, 
                { "data": "ProjectCode" }, 
                { "data": "NPdre" },
                { 
                    "data": "IdPadre", 
                    render: function(data) {  
                        return '<button type="button" class="btn btn-secondary btn-correo" data-id="' + data + '" data-toggle="modal" data-target="#Correo"><strong>Correo</strong></button>'; 
                    }
                },
                {  
                    data: "IdPadre", 
                    render: function(data) { 
                        return `<button class="btn btn-warning btn-xs btn-cambiarValidar" data-id="${data}" style="margin: 1px" title="VALIDAR"><strong>VALIDAR</strong></button>`; 
                    } 
                },
                { 
                    data: "IdPadre", 
                    render: function(data) { 
                        return `<button class="btn btn-danger btn-xs btn-cambiar" data-id="${data}" style="margin: 1px" title="CERRAR">CERRAR</button>`;  
                    } 
                }
            ]   
        });
    }
    
    // Inicializar eventos de botones
    initializeButtonEvents();
    initializeTableEvents();
    initializeSubQueryTable();
}

// Función para inicializar eventos de botones
function initializeButtonEvents() {
    // Botón cambiar estado (CERRAR)
    $('#example tbody').on('click', '.btn-cambiar', function() {
        var idHijOT = $(this).data('id');
        // Confirmación antes de cerrar el proyecto
        Swal.fire({
            title: '¿Cerrar Proyecto?',
            html: 'Estás a punto de cerrar este proyecto.<br><br><strong>Nota:</strong> Solo se puede cerrar si todas las semanas programadas de los procesos están cerradas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#27ae60',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar proyecto',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'consultasAjax/consulta10_2.php',
                    method: 'POST',
                    data: { IdHijOT: idHijOT },
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Proyecto Cerrado!',
                                text: result.message,
                                confirmButtonColor: '#27ae60'
                            }); 
                            window.mainTable.ajax.reload(null, false); 
                            fetchSubQueryResults();     
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se puede cerrar',
                                html: result.message,
                                confirmButtonColor: '#e67e22',
                                footer: '<a href="#" onclick="document.getElementById(\'reporte-procesos-no-cerrados\').click(); return false;">Ver reporte de procesos pendientes</a>'
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error al cambiar el estado:', textStatus, errorThrown);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al procesar la solicitud'
                        });
                    }
                });
            }
        });
    });
    
    // Botón cambiar a validar
    $('#example tbody').on('click', '.btn-cambiarValidar', function() {
        var idHijOT = $(this).data('id');

        
        $.ajax({
            url: 'consultasAjax/consulta10_1.php',
            method: 'POST',
            data: { IdHijOT: idHijOT },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Validación exitosa',
                        text: result.message
                    });
                    window.mainTable.ajax.reload(null, false);
                    fetchSubQueryResults();        
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en validación',
                        text: result.message
                    });
                }
            }
        });              
    });
}

// Función para inicializar eventos de tabla
function initializeTableEvents() {
    // Click en filas de la tabla principal
    $('#example tbody').on('click', 'tr', function () {
        var idPadre = window.mainTable.row(this).data().IdPadre;
        $('#example tbody tr').removeClass('selected-row');
        $(this).addClass('selected-row');
        fetchSubQueryResults(idPadre);
    });
}

// Función para obtener resultados de subconsulta
function fetchSubQueryResults(idPadre) {
    console.log('Obteniendo subconsulta para ID:', idPadre);
    $.ajax({
        url: "consultasAjax/consulta8.php", 
        method: "POST", 
        data: { idPadre: idPadre, tipoConsulta: 1 }, 
        dataType: "json",
        success: function(data) { 
            if (window.subQueryTable) {
                window.subQueryTable.clear().rows.add(data).draw();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en subconsulta:', error);
        }
    });
}

// Función para inicializar la tabla de subconsulta
function initializeSubQueryTable() {
    if (!$.fn.DataTable.isDataTable('#subQueryResults')) {
        window.subQueryTable = $("#subQueryResults").DataTable({
            ajax: "consultasAjax/consulta8.php",
            columns: [
                { "data": "ITEM" },
                { "data": "PorcenCalProy" },
                { "data": "CANTI" },
                { "data": "NPdre" },
                { "data": "HHFab" },
                { "data": "PesPptoTotaKg" },
                { "data": "PeSoldaduraKg" },
                { "data": "MO001_1" },
                { "data": "MO001_2" },
                { "data": "MO002" },
                { "data": "MO004" },
                { "data": "MO014" },
                { "data": "MO008_1" },
                { "data": "MO008_2" },
                { "data": "MO007_1" },
                { "data": "MO007_2" },
                { "data": "MO003" },
                { "data": "MO015" },
                { "data": "MO005" },
                { "data": "MO009" },
                { "data": "MO004P" },
                { "data": "MO006" },
                { "data": "MO012" },
                { "data": "MO011" },
                { "data": "MO020" },
                { "data": "MO010" },
                { "data": "MO050", visible: false, defaultContent: "0", className: "col-proceso-especial" },
                { "data": "MO060", visible: false, defaultContent: "0", className: "col-proceso-especial" },
                { "data": "MO070", visible: false, defaultContent: "0", className: "col-proceso-especial" },
                { "data": "MO080", visible: false, defaultContent: "0", className: "col-proceso-especial" },
                { "data": "SUT", render: function(data) { return parseFloat(data).toFixed(2); } }
            ]
        });
        
        // Aplicar eventos después de crear la tabla
        window.subQueryTable.on('draw', function() {
            aplicarColoresEnFilas();
            aplicarColoresEnBORDES();
        });
        
        // Inicializar evento de clic derecho para div flotante
        initializeContextMenu();
        
        // Inicializar evento de doble click para edición en línea
        initializeDoubleClickEvents();
        
        // Inicializar toggle de Procesos Especiales
        initializeToggleProcesosEspeciales();
    }
}

// Toggle para mostrar/ocultar Procesos Especiales en ProgramarSemana
function initializeToggleProcesosEspeciales() {
    $('#toggleProcesosEspeciales').off('click').on('click', function() {
        var $btn = $(this);
        var isActive = $btn.hasClass('active');
        
        // Columnas de procesos especiales (índices 26, 27, 28, 29 - MO050, MO060, MO070, MO080)
        var columnsMO050 = window.subQueryTable.column(26);
        var columnsMO060 = window.subQueryTable.column(27);
        var columnsMO070 = window.subQueryTable.column(28);
        var columnsMO080 = window.subQueryTable.column(29);
        
        if (isActive) {
            // Ocultar columnas
            columnsMO050.visible(false);
            columnsMO060.visible(false);
            columnsMO070.visible(false);
            columnsMO080.visible(false);
            $btn.removeClass('active');
            $btn.find('.toggle-icon').text('+');
            // Ocultar headers del grupo
            $('.th-procesos-especiales').hide();
        } else {
            // Mostrar columnas
            columnsMO050.visible(true);
            columnsMO060.visible(true);
            columnsMO070.visible(true);
            columnsMO080.visible(true);
            $btn.addClass('active');
            $btn.find('.toggle-icon').text('×');
            // Mostrar headers del grupo
            $('.th-procesos-especiales').show();
        }
    });
}

// Función para inicializar eventos de doble click (edición en línea)
function initializeDoubleClickEvents() {
    $('#subQueryResults tbody').on('dblclick', 'td', function () {
        if (!window.subQueryTable) {
            console.error('subQueryTable no está definida para doble click');
            return;
        }
        
        var cell = window.subQueryTable.cell(this);
        var rowData = window.subQueryTable.row(cell.index().row).data();
        var columnIndex = cell.index().column;
        
        // Obtener el nombre real del campo desde la configuración de DataTables
        var columnDef = window.subQueryTable.settings()[0].aoColumns[columnIndex];
        var fieldName = columnDef.mData || columnDef.data;
        
        // Debug detallado para identificar el problema
        console.log('=== DOBLE CLICK DEBUG ===');
        console.log('Índice de columna:', columnIndex);
        console.log('Nombre del campo:', fieldName);
        console.log('Valor de celda (cell.data()):', cell.data());
        console.log('Valor en rowData[' + fieldName + ']:', rowData[fieldName]);
        console.log('Configuración de columna:', columnDef);
        console.log('Datos completos de la fila:', rowData);
        console.log('========================');

        var editableColumns = ["PorcenCalProy", "MO001_1", "MO001_2", "MO002", "MO004", "MO014", "MO008_1", "MO008_2", "MO007_1", "MO007_2", "MO003", "MO015", "MO005", "MO009", "MO004P", "MO006", "MO012", "MO011", "MO020", "MO010", "MO050", "MO060", "MO070", "MO080"];
        
        // Editar columnas numéricas (incluye PorcenCalProy y columnas MO)
        if (editableColumns.includes(fieldName)) {
            console.log('✅ Editando columna numérica:', fieldName);
            editNumericColumn(cell, rowData, fieldName);
        }
        else {
            console.log('❌ Columna no editable:', fieldName, 'en índice:', columnIndex);
        }
    });
}

// Función para editar columnas numéricas (PorcenCalProy y columnas MO)
function editNumericColumn(cell, rowData, fieldName) {
    var originalValue = cell.data(); // Usar el valor directo de la celda
    console.log('Editando columna', fieldName, '- Valor original:', originalValue);
    
    var input = $('<input type="number" class="form-control" step="0.01" />').val(originalValue);
    
    input.on('blur', function() {
        var newValue = $(this).val();
        console.log('Nuevo valor', fieldName + ':', newValue, 'Original:', originalValue);
        if (newValue !== originalValue) {
            updateCellValue(cell, rowData, fieldName, newValue, originalValue);
        } else {
            cell.data(originalValue).draw();
        }
    });
    
    input.on('keydown', function(e) {
        if (e.key === 'Enter') {
            $(this).blur();
        }
        if (e.key === 'Escape') {
            cell.data(originalValue).draw();
        }
    });

    $(cell.node()).html(input);
    input.focus().select();
}

// Función para actualizar valor de celda en la base de datos
function updateCellValue(cell, rowData, fieldName, newValue, originalValue) {
    console.log('=== UPDATE CELL VALUE DEBUG ===');
    console.log('Campo a actualizar:', fieldName);
    console.log('Valor nuevo:', newValue);
    console.log('Valor original:', originalValue);
    console.log('IdHijOT:', rowData.IdHijOT);
    console.log('Índice de la celda:', cell.index());
    console.log('===============================');
    
    // Mostrar indicador de carga en la celda
    $(cell.node()).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
    
    $.ajax({
        url: "consultasAjax/consulta9.php",
        method: "POST",
        data: {
            IdHijOT: rowData.IdHijOT,
            fieldName: fieldName,
            newValue: newValue
        },
        success: function(response) {
            try {
                var result = JSON.parse(response);
                if (result.success) {
                    // Actualizar la celda con el nuevo valor
                    cell.data(newValue).draw();
                    console.log('✅ Celda actualizada exitosamente en campo:', fieldName);
                    
                    // Mostrar confirmación visual breve
                    $(cell.node()).css('background-color', '#d4edda');
                    setTimeout(function() {
                        $(cell.node()).css('background-color', '');
                    }, 1000);
                    
                    // Recargar subconsulta si es necesario para mantener sincronización
                    if (typeof fetchSubQueryResults === 'function') {
                        fetchSubQueryResults(rowData.IdPadre);
                    }
                    // Si se actualizó el límite de proyecto (PorcenCalProy), recalcular el avance y enviar a SAP
                    if (fieldName === 'PorcenCalProy') {
                        var payload = {
                            idPadreOT: rowData.IdPadre || rowData.IdPadreOT || window.idPadreOT || null,
                            codPdre: rowData.CodPdre || rowData.codPdre || window.codPdre || null,
                            projectCode: window.projectCode || rowData.ProjectCode || rowData.projectCode || null
                        };
                        console.log('PorcenCalProy actualizado; solicitando recalculo avance real proyecto', payload);
                        if (payload.idPadreOT && payload.codPdre) {
                            recalcularAvanceRealProyecto(payload);
                        } else {
                            console.warn('No se pudo recalcular: faltan idPadreOT o codPdre', payload);
                        }
                    }
                } else {
                    console.error('❌ Error del servidor:', result.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: result.message || 'Error desconocido',
                        showConfirmButton: true
                    });
                    cell.data(originalValue).draw();
                }
            } catch (e) {
                console.error('❌ Error parsing response:', e, 'Response:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de respuesta',
                    text: 'La respuesta del servidor no es válida'
                });
                cell.data(originalValue).draw();
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX:', error, 'Status:', status, 'Response:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'Error de conexión al actualizar: ' + error
            });
            cell.data(originalValue).draw();
        }
    });
}

// Función para inicializar el menú contextual (clic derecho)
function initializeContextMenu() {
    var editableColumns = [ 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29];
    
    $('#subQueryResults tbody').on('contextmenu', 'td', function(e) {
        console.log('Clic derecho detectado en tabla');
        
        if (!window.subQueryTable) {
            console.error('subQueryTable no está definida');
            return;
        }
        
        var columnIndex = window.subQueryTable.cell(this).index().column;
        var cellValue = window.subQueryTable.cell(this).data();
        
        console.log('Columna:', columnIndex, 'Valor:', cellValue);
        console.log('Es columna editable:', editableColumns.includes(columnIndex));
        
        if (editableColumns.includes(columnIndex)) {
            e.preventDefault();
            showTableOptions(e, $(this), columnIndex, cellValue);
        }
    });
}

// Función showTableOptions migrada desde PHP
function showTableOptions(e, cell, columnIndex, cellValue) {
    console.log('showTableOptions llamada:', {e, cell, columnIndex, cellValue});
    // Evitar reposicionamiento si el div ya está visible (solo mover cuando está oculto)
    const div = document.getElementById('div-flotante');
    if(div){
        const isHidden = (div.style.display === 'none') || (getComputedStyle(div).display === 'none');
        if(isHidden){
            setDivFlotantePosition(e);
        }
    } else {
        setDivFlotantePosition(e); // fallback si no existe aún
    }
    insertDynamicContent(cell, columnIndex, cellValue);
}

// Variables globales para el div flotante (migradas desde PHP)
var Peso, projectCode, codPdre, idPadreOT, columnName, NPdre;

// Función para insertar contenido dinámico (migrada desde PHP)
function insertDynamicContent(cell, columnIndex, cellValue) {
    console.log('insertDynamicContent llamada:', {cell, columnIndex, cellValue});
    
    if (!window.subQueryTable) {
        console.error('subQueryTable no está disponible');
        return;
    }
    
    var content = '';
    var row = window.subQueryTable.row(cell.closest('tr')).data();
    
    // Asignar variables globales
    window.Peso = cellValue; 
    window.codPdre = row.CodPdre;
    window.projectCode = row.OTPADRE;  
    window.idPadreOT = row.IdPadreOT;
    window.NPdre = row.NPdre;
    window.columnName = window.subQueryTable.settings()[0].aoColumns[columnIndex].mData;
    var columnTitle = window.subQueryTable.settings()[0].aoColumns[columnIndex].sTitle;

    // Asignar variables globales de respaldo para compatibilidad
    Peso1 = cellValue;
    codPdre1 = row.CodPdre;
    projectCode1 = row.OTPADRE;
    idPadreOT1 = row.IdPadreOT;
    Proceso1 = window.columnName;

    // Definir los encabezados para el título
    var headers = [window.projectCode + ' - ' + window.NPdre, columnTitle];
    insertHeaderTitles(headers);

    // Llamar a ListarDatos si está disponible
    if (typeof ListarDatos === 'function' && typeof ListarData === 'function') {
        ListarDatos(window.idPadreOT, window.codPdre, window.columnName, function(response) {
            ListarData(response);
        });
    }

    // Crear contenido de tabla básico
    content += '<table id="tabla-dinamica" class="tabla-dinamica" style="width: 100%; border: 1px solid #ddd; border-collapse: collapse;">';
    content += '<thead>';
    content += '<tr><th>AÑO</th><th>SEMANA</th><th>PROG.HH</th><th>HORAS</th><th>AV. FISICO</th><th>FABRIC.</th><th style="width: 13%">Cerrar Semana</th><th style="width: 10%">Eliminar</th></tr>';
    content += '</thead>';
    content += '<tbody></tbody></table>';

    // Insertar la tabla dentro del div flotante (una sola vez, evitar duplicados)
    const $div = $('#div-flotante');
    $div.find('.tabla-interna').html(content);

    // Preparar objeto de debug si no existe
    if(!window.__divFloatDebug){
        window.__divFloatDebug = {
            enabled: true,
            history: [],
            log: function(type, data){
                if(!this.enabled) return;
                this.history.push({ts: Date.now(), type, data});
                console.log('[DivFlotante DEBUG]', type, data);
            }
        };
    }

    // Mostrar (si estaba oculto) y posicionar solo si oculto y no bloqueado
    const wasHidden = $div.is(':hidden');
    const lock = !!window.divFlotanteLock;
    if(wasHidden){
        $div.show();
        if(!lock){
            // Centrar únicamente la primera apertura de esta sesión o cuando estaba oculto
            $div.css({
                left: '50%',
                top: '50%',
                transform: 'translate(-50%, -50%)',
                position: 'fixed'
            });
            window.__divFloatDebug.log('center-on-open', {reason: 'wasHidden', lock});
        } else {
            window.__divFloatDebug.log('skip-center-locked', {reason: 'wasHidden', lock});
        }
    } else {
        window.__divFloatDebug.log('retain-position', {reason: 'alreadyVisible', lock});
    }
    // Si está visible y bloqueado, no modificamos left/top/transform

    // Asegurar drag restringido sigue activo al regenerar contenido
    restrictFloatingDivDrag();

   
}

// ===============================
// FUNCIONES LISTAR DATOS - MIGRADAS DESDE PHP
// ===============================

// Variables globales para el div flotante
window.totalAvance = 0;
window.abrirSemanas = [];

// Hacer las variables disponibles globalmente
var totalAvance = window.totalAvance;
var abrirSemanas = window.abrirSemanas;

// Función para obtener datos de la base de datos
function ListarDatos(idPadreOT, codPdre, columnName, callback) {
    console.log('ListarDatos llamada:', {idPadreOT, codPdre, columnName});
    
    $.ajax({
        url: 'consultasAjax1/consulta2.php',
        method: 'POST',
        data: { idPadreOT: idPadreOT, codPdre: codPdre, proceso: columnName },
        dataType: 'json', // Especificar que esperamos JSON
        success: function(response) {
            console.log('Respuesta de consulta2.php:', response);
            console.log('Tipo de respuesta:', typeof response);
            
            // La respuesta ya está parseada por jQuery cuando especificamos dataType: 'json'
            if (Array.isArray(response)) {
                console.log('Datos válidos recibidos:', response.length, 'registros');
                callback(response);
            } else if (response && response.error) {
                console.error('Error del servidor:', response.error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: response.error
                });
            } else {
                console.error('Error: La respuesta no es un arreglo válido', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error en los datos',
                    text: 'La respuesta del servidor no es válida'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en ListarDatos:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            // Mostrar error al usuario
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor: ' + error
            });
        }
    });
}

// Función para renderizar los datos en la tabla del div flotante
function ListarData(data) {
    console.log('ListarData llamada con:', data);
    
    // Reinicializar variables globales
    window.totalAvance = 0;
    window.abrirSemanas = []; // Limpiar la lista al cargar nuevos datos
    
    var tableBody = $('#div-flotante').find('.tabla-dinamica tbody');
    var semanasGuardadas = [];
      
    tableBody.empty();

    if (data && Array.isArray(data) && data.length > 0) {
        console.log('=== DEBUGGING LISTAR DATA ===');
        console.log('Total de registros recibidos:', data.length);
        
        data.forEach(function(row, index) {
            console.log(`--- Registro ${index + 1} ---`);
            console.log('Datos completos del registro:', row);
            console.log('IdPrograma específico:', row.IdPrograma);
            console.log('Tipo de IdPrograma:', typeof row.IdPrograma);
            console.log('IdPrograma es null/undefined?', row.IdPrograma == null);
            console.log('Todas las propiedades del objeto:', Object.keys(row));
            
            var semana = row.Semana || '';  
            if (semanasGuardadas.includes(semana)) {
                console.log("Semana duplicada detectada: " + semana);
                return;
            }
            semanasGuardadas.push(semana);

            var avance = parseFloat(row.Avance) || 0;
            window.totalAvance += avance;

            var botonCerrar = '';
            if (row.Estado == 1) {
                botonCerrar = '<button type="button" class="cerrar-row-btn" style="background-color: #5ef518; color: black; border: none; border-radius: 5px; cursor: pointer;">Abierto</button>';
                window.abrirSemanas.push(row.IdPrograma);
            } else if (row.Estado == 2) {
                botonCerrar = '<button type="button" class="cerrar-row-btn" style="background-color: #95a5a6; color: black; border: none; border-radius: 5px; cursor: pointer;">Cerrado</button>';
            }

            var idProgramaValue = row.IdPrograma || 'SIN_ID';
            console.log('Valor final de IdPrograma para HTML:', idProgramaValue);

            var rowHtml = '<tr>';
            rowHtml += '<td class="hidden" data-field="IdPrograma">' + idProgramaValue + '</td>';
            rowHtml += '<td class="hidden" data-field="PESO">' + (row.MO_Value || '') + '</td>';
            rowHtml += '<td class="editable" data-field="Aniio">' + (row.Aniio || '') + '</td>';
            rowHtml += '<td class="editable" data-field="Semana">' + (row.Semana || '') + '</td>';
            rowHtml += '<td class="editable" data-field="Avance">' + (row.Avance || '') + '%</td>';
            rowHtml += '<td class="totHoras" data-field="TotHoras">' + (row.TotHoras || '') + '</td>';
            rowHtml += '<td class="editable" data-field="AvanceReal">' + (row.AvanceReal || '') + '%</td>';
        
            // Nota: La columna puede venir como "Fabricacion" o "fabricacion" dependiendo de la BD
            var fabricacionValue = (row.fabricacion || row.Fabricacion || 'tama').toLowerCase();
            var isDisabled = (row.Estado == 2) ? ' disabled' : '';
            
            // Detectar si es proceso especial (MO050, MO060, MO070, MO080)
            var currentProcess = window.columnName || '';
            var isProcesoEspecial = ['MO050', 'MO060', 'MO070', 'MO080'].includes(currentProcess);
            
            var selectFabricacion = '<select class="select-fabricacion" data-field="fabricacion" data-id-programa="' + idProgramaValue + '" style="padding: 3px; border-radius: 4px; border: 1px solid #ccc;"' + isDisabled + '>';
            if (isProcesoEspecial) {
                // Para procesos especiales, solo mostrar svt-especiales
                selectFabricacion += '<option value="svt-especiales" selected>SVT-ESPECIALES</option>';
            } else {
                selectFabricacion += '<option value="tama"' + (fabricacionValue === 'tama' ? ' selected' : '') + '>TAMA</option>';
                selectFabricacion += '<option value="svt"' + (fabricacionValue === 'svt' ? ' selected' : '') + '>SVT</option>';
            }
            selectFabricacion += '</select>';
            rowHtml += '<td data-field="fabricacion">' + selectFabricacion + '</td>';
            rowHtml += '<td class="hidden" data-field="Estado">' + (row.Estado || '') + '</td>';
            rowHtml += '<td><center>' + botonCerrar + '</center></td>';
            rowHtml += '<td><button type="button" class="delete-row-btn" style="background-color: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">Eliminar</button></td>';
            rowHtml += '</tr>';
            
            tableBody.append(rowHtml);
        });
    } else {
        tableBody.append('<tr><td colspan="9">No se encontraron datos.</td></tr>');
    }
    
    console.log('Total avance:', totalAvance);
    
    // Evento para cambio de fabricacion en filas existentes
    initializeFabricacionChangeEvent();
    
    // Ya no usamos eventos blur - solo double-click para editar
    
    // ============================================================================
    // BASELINE: Verificar y crear automáticamente si es primera programación
    // ============================================================================
    
    // Guardar el ID del programa actual para referencia
    let idProgramaActual = null;
    if (data && data.length > 0) {
        idProgramaActual = data[0].IdPrograma;
        $('#div-flotante').data('current-programa', idProgramaActual);
        window.currentIdPrograma = idProgramaActual;
        
        console.log('🔍 BASELINE DEBUG - ID Programa detectado:', idProgramaActual);
        console.log('🔍 BASELINE DEBUG - Datos del proceso:', {
            otpadre: data[0].otpadre,
            CodProyecto: data[0].CodProyecto,
            CodProceso: data[0].CodProceso,
            CodPdre: data[0].CodPdre
        });
        console.log('🔍 BASELINE DEBUG - Iniciando verificación de baseline...');
        
        // Verificar si ya tiene baseline
        verificarBaseline(idProgramaActual).then(function(resultado) {
            console.log('🔍 BASELINE DEBUG - Resultado verificación:', resultado);
            if (!resultado.tiene_baseline) {
                // No tiene baseline - mostrar botón para crear baseline manualmente
                console.log('✅ BASELINE DEBUG - Programa sin baseline detectado:', idProgramaActual);
                agregarBotonesBaseline(idProgramaActual, false); // false = sin baseline
            } else {
                // Ya tiene baseline, solo agregar botón de comparación
                console.log('✅ BASELINE DEBUG - Programa ya tiene baseline, agregando botón de comparación');
                agregarBotonesBaseline(idProgramaActual, true); // true = con baseline
            }
        }).catch(function(error) {
            console.error('❌ BASELINE DEBUG - Error verificando baseline:', error);
            // En caso de error, mostrar botón para crear baseline
            agregarBotonesBaseline(idProgramaActual, false);
        });
    } else {
        // No hay datos existentes - es un proceso completamente nuevo SIN PROGRAMACIÓN
        console.log('🔍 BASELINE DEBUG - No hay datos existentes, proceso sin programación');
        console.log('� BASELINE DEBUG - No se muestran botones de baseline hasta que se programen semanas');
        
        // NO mostrar botones de baseline cuando no hay datos programados
        // Los botones aparecerán automáticamente después de programar y guardar la primera vez
        $('.baseline-controls').remove(); // Limpiar cualquier botón existente
        
        console.log('⏳ BASELINE DEBUG - Esperando a que el usuario programe semanas...');
    }
}

// Función para agregar botones de baseline al div flotante
function agregarBotonesBaseline(idPrograma, tieneBaseline) {
    // Remover botones existentes para evitar duplicados
    $('.baseline-controls').remove();
    
    let botonHtml = '<div class="mt-3 text-center baseline-controls">';
    
    if (tieneBaseline) {
        // Ya tiene baseline - mostrar botón de comparación
        botonHtml += '<button type="button" id="btn-comparar-baseline" class="btn btn-info btn-sm me-2" onclick="mostrarComparacionBaseline(\'' + idPrograma + '\')">';
        botonHtml += '📊 Ver Comparación con Línea Base';
        botonHtml += '</button>';
    } else {
        // No tiene baseline - mostrar botón para crear + información
        botonHtml += '<div class="alert alert-warning alert-sm mb-2">';
        botonHtml += '<small><strong>⚠️ Este programa no tiene línea base.</strong> Puedes crear una línea base con la programación actual para futuras comparaciones.</small>';
        botonHtml += '</div>';
        
        botonHtml += '<button type="button" id="btn-crear-baseline" class="btn btn-success btn-sm me-2" onclick="crearBaselineManual(\'' + idPrograma + '\')">';
        botonHtml += '📌 Crear Línea Base Actual';
        botonHtml += '</button>';
    }
    
    botonHtml += '</div>';
    
    // Insertar después de la tabla
    $('#div-flotante .tabla-dinamica').after(botonHtml);
}

// Función para crear baseline manualmente (desde botón)
function crearBaselineManual(idPrograma) {
    Swal.fire({
        title: '📌 Crear Línea Base',
        text: '¿Quieres guardar la programación actual como línea base para futuras comparaciones?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Crear Línea Base',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            crearBaseline(idPrograma).then(function(response) {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Línea Base Creada',
                    text: 'La programación actual se ha guardado como línea base. Ahora puedes hacer comparaciones.',
                    showConfirmButton: false,
                    timer: 2000
                });
                
                // Actualizar botones para mostrar comparación
                agregarBotonesBaseline(idPrograma, true);
                
            }).catch(function(error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo crear la línea base: ' + error
                });
            });
        }
    });
}

// Función para cerrar div flotante
function closeDivFlotante() {
    console.log('Cerrando div flotante via closeDivFlotante');
    $('#div-flotante').hide();
    $('#overlay').hide();
}

// Función para inicializar evento de cambio de fabricacion en filas existentes
function initializeFabricacionChangeEvent() {
    $('#div-flotante .tabla-dinamica').off('change', '.select-fabricacion').on('change', '.select-fabricacion', function() {
        var $select = $(this);
        var idPrograma = $select.data('id-programa');
        var newValue = $select.val();
        
        // Si es una fila nueva (sin IdPrograma guardado), no hacer nada - se guardará con el resto
        if (!idPrograma || idPrograma === 'SIN_ID') {
            console.log('Fila nueva, fabricacion se guardará al guardar datos');
            return;
        }
        
        // Verificar que la semana no esté cerrada
        var $row = $select.closest('tr');
        var estado = $row.find('td[data-field="Estado"]').text();
        if (estado == '2') {
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'No se puede modificar una semana cerrada'
            });
            // Revertir al valor anterior
            return;
        }
        
        // Enviar actualización al servidor
        $.ajax({
            url: 'consultasAjax/actualizar_fabricacion.php',
            method: 'POST',
            data: {
                idPrograma: idPrograma,
                fabricacion: newValue
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Fabricación actualizada:', response.message);
                    // Feedback visual sutil
                    $select.css('background-color', '#d4edda');
                    setTimeout(function() {
                        $select.css('background-color', '');
                    }, 1000);
                } else {
                    console.error('Error al actualizar fabricación:', response.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX actualizando fabricación:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo actualizar la fabricación'
                });
            }
        });
    });
}

// Hacer funciones disponibles globalmente
window.initializeFabricacionChangeEvent = initializeFabricacionChangeEvent;
window.fetchSubQueryResults = fetchSubQueryResults;
window.showTableOptions = showTableOptions;
window.insertDynamicContent = insertDynamicContent;
window.ListarDatos = ListarDatos;
window.ListarData = ListarData;
window.closeDivFlotante = closeDivFlotante;

// ============================================================================
// FUNCIONES DE BASELINE (LÍNEA BASE)
// ============================================================================

// Verificar si tiene baseline al cargar datos
function verificarBaseline(idPrograma) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'consultasAjax1/baseline_manager.php',
            type: 'POST',
            data: {
                accion: 'verificar_baseline',
                id_programa: idPrograma
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(response.error);
                }
            },
            error: function(xhr, status, error) {
                reject('Error al verificar baseline: ' + error);
            }
        });
    });
}

// Verificar baseline por datos del proceso (cuando no hay IdPrograma)
function verificarBaselinePorProceso(otpadre, codProyecto, codProceso, codPdre) {
    return new Promise((resolve, reject) => {
        console.log('🔍 Verificando baseline por proceso:', {otpadre, codProyecto, codProceso, codPdre});
        
        $.ajax({
            url: 'consultasAjax1/verificar_baseline_proceso.php',
            type: 'POST',
            data: {
                otpadre: otpadre,
                cod_proyecto: codProyecto,
                cod_proceso: codProceso,
                cod_pdre: codPdre
            },
            dataType: 'json',
            success: function(response) {
                console.log('🔍 Respuesta verificación por proceso:', response);
                if (response.success !== undefined) {
                    resolve({
                        tiene_baseline: response.tiene_baseline || false,
                        baseline_count: response.baseline_count || 0,
                        proceso: response.proceso || codProceso
                    });
                } else {
                    reject('Respuesta inválida del servidor');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error verificando baseline por proceso:', error);
                console.error('Respuesta del servidor:', xhr.responseText);
                
                // En caso de error, asumir que no hay baseline
                resolve({
                    tiene_baseline: false,
                    baseline_count: 0,
                    proceso: codProceso
                });
            }
        });
    });
}

// Crear baseline automáticamente
function crearBaseline(idPrograma) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'consultasAjax1/baseline_manager.php',
            type: 'POST',
            data: {
                accion: 'crear_baseline',
                id_programa: idPrograma
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Baseline creado:', response.message);
                    resolve(response);
                } else {
                    console.warn('No se pudo crear baseline:', response.message);
                    reject(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al crear baseline:', error);
                reject('Error al crear baseline: ' + error);
            }
        });
    });
}

// Obtener comparación baseline vs actual
function obtenerComparacion(idPrograma) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'consultasAjax1/baseline_manager.php',
            type: 'POST',
            data: {
                accion: 'obtener_comparacion',
                id_programa: idPrograma
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(response.error);
                }
            },
            error: function(xhr, status, error) {
                reject('Error al obtener comparación: ' + error);
            }
        });
    });
}

// Mostrar comparación en el div flotante
function mostrarComparacionBaseline(idPrograma) {
    console.log('📊 MOSTRAR COMPARACION - Inicio para ID:', idPrograma);
    
    // ⚠️ PRESERVAR VARIABLES GLOBALES antes de cambiar el contenido
    console.log('💾 PRESERVAR VARIABLES - Estado actual:', {
        idPadreOT: window.idPadreOT,
        codPdre: window.codPdre,
        columnName: window.columnName,
        currentIdPrograma: window.currentIdPrograma
    });
    
    // Guardar variables en el div flotante para recuperarlas después
    $('#div-flotante').data('preserved-vars', {
        idPadreOT: window.idPadreOT,
        codPdre: window.codPdre,
        columnName: window.columnName,
        currentIdPrograma: window.currentIdPrograma,
        projectCode: window.projectCode,
        NPdre: window.NPdre,
        Peso: window.Peso
    });
    
    console.log('💾 Variables preservadas en div-flotante data');
    
    obtenerComparacion(idPrograma).then(function(data) {
        // Crear HTML para mostrar comparación en el div flotante
        let html = '<div class="baseline-comparison" style="padding: 15px;">';
        html += '<div style="text-align: center; margin-bottom: 20px;">';
        html += '<h4 style="color: #2c3e50; margin-bottom: 10px;">📊 Comparación: Línea Base vs Actual</h4>';
        html += '<small style="color: #7f8c8d;">Programa ID: ' + idPrograma + '</small>';
        html += '</div>';
        
        // Resumen de cambios
        if (data.comparacion.resumen.length > 0) {
            html += '<div style="background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 10px; margin-bottom: 15px;">';
            html += '<strong style="color: #0c5460;">📋 Cambios Detectados:</strong>';
            html += '<ul style="margin: 10px 0 0 0; padding-left: 20px;">';
            data.comparacion.resumen.forEach(function(cambio) {
                html += '<li style="margin: 5px 0; color: #495057;">' + cambio + '</li>';
            });
            html += '</ul></div>';
        } else {
            html += '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 10px; margin-bottom: 15px; color: #155724;">';
            html += '✅ <strong>No hay cambios</strong> respecto a la línea base';
            html += '</div>';
        }
        
        // Tabla comparativa con estilos mejorados
        html += '<table style="width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; margin-bottom: 15px;">';
        html += '<thead>';
        html += '<tr style="background-color: #f8f9fa;">';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Año/Semana</th>';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Avance Base</th>';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Avance Actual</th>';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Horas Base</th>';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Horas Actual</th>';
        html += '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center; font-size: 12px;">Estado</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        // Combinar datos para mostrar
        let todasLasSemanas = new Map();
        
        // Debug: Ver qué datos estamos recibiendo
        console.log('🔍 BASELINE DEBUG - Datos baseline recibidos:', data.datos_baseline);
        console.log('🔍 BASELINE DEBUG - Datos actuales recibidos:', data.datos_actuales);
        console.log('🔍 BASELINE DEBUG - Total registros baseline:', data.datos_baseline.length);
        console.log('🔍 BASELINE DEBUG - Total registros actuales:', data.datos_actuales.length);
        
        // Verificar específicamente si viene la semana 4 del baseline
        let semana4Baseline = data.datos_baseline.find(item => item.semana == 4);
        console.log('🔍 SEMANA 4 BASELINE - ¿Existe?', semana4Baseline ? 'SÍ' : 'NO');
        if (semana4Baseline) {
            console.log('🔍 SEMANA 4 BASELINE - Datos:', semana4Baseline);
        }
        
        // Agregar baseline - estas son las semanas originales (incluye eliminadas)
        data.datos_baseline.forEach(function(item) {
            let key = item.anio + '-' + item.semana;
            console.log('🔍 BASELINE DEBUG - Procesando baseline:', key, item);
            todasLasSemanas.set(key, {
                anio: item.anio,
                semana: item.semana,
                avance_base: item.avance,
                horas_base: item.tot_horas,
                avance_actual: null,
                horas_actual: null,
                estado: 'ELIMINADO'  // Inicialmente marcamos como eliminado
            });
        });
        
        console.log('🔍 BASELINE DEBUG - Mapa después de baseline:', Array.from(todasLasSemanas.entries()));
        
        // Agregar actual
        data.datos_actuales.forEach(function(item) {
            let key = item.anio + '-' + item.semana;
            console.log('🔍 BASELINE DEBUG - Procesando actual:', key, item);
            
            if (todasLasSemanas.has(key)) {
                // Existe en baseline, actualizar datos actuales
                let semana = todasLasSemanas.get(key);
                semana.avance_actual = item.avance;
                semana.horas_actual = item.tot_horas;
                semana.estado = (semana.avance_base == item.avance && semana.horas_base == item.tot_horas) ? 'SIN CAMBIOS' : 'MODIFICADO';
                console.log('🔍 BASELINE DEBUG - Actualizado semana existente:', key, semana);
            } else {
                // No existe en baseline, es una semana nueva
                todasLasSemanas.set(key, {
                    anio: item.anio,
                    semana: item.semana,
                    avance_base: null,
                    horas_base: null,
                    avance_actual: item.avance,
                    horas_actual: item.tot_horas,
                    estado: 'AGREGADO'
                });
                console.log('🔍 BASELINE DEBUG - Agregada semana nueva:', key);
            }
        });
        
        console.log('🔍 BASELINE DEBUG - Mapa final después de procesar actual:', Array.from(todasLasSemanas.entries()));
        
        // Debug específico: Verificar semanas eliminadas
        let semanasEliminadas = Array.from(todasLasSemanas.entries()).filter(([key, semana]) => semana.estado === 'ELIMINADO');
        console.log('🔍 SEMANAS ELIMINADAS - Total encontradas:', semanasEliminadas.length);
        console.log('🔍 SEMANAS ELIMINADAS - Detalles:', semanasEliminadas);
        
        // Verificar específicamente la semana 4
        let semana4Final = todasLasSemanas.get('2025-4');
        console.log('🔍 SEMANA 4 FINAL - ¿Existe en mapa?', semana4Final ? 'SÍ' : 'NO');
        if (semana4Final) {
            console.log('🔍 SEMANA 4 FINAL - Estado:', semana4Final.estado);
            console.log('🔍 SEMANA 4 FINAL - Datos completos:', semana4Final);
        }
        
        // Mostrar todas las semanas ordenadas
        Array.from(todasLasSemanas.entries())
            .sort((a, b) => {
                let [keyA, valA] = a;
                let [keyB, valB] = b;
                return (valA.anio - valB.anio) || (valA.semana - valB.semana);
            })
            .forEach(function([key, semana]) {
                let estadoClass = '';
                let estadoIcon = '';
                
                switch (semana.estado) {
                    case 'SIN CAMBIOS':
                        estadoClass = 'background-color: #f8f9fa;';
                        estadoIcon = '✅';
                        break;
                    case 'MODIFICADO':
                        estadoClass = 'background-color: #fff3cd;';
                        estadoIcon = '⚠️';
                        break;
                    case 'AGREGADO':
                        estadoClass = 'background-color: #d4edda;';
                        estadoIcon = '➕';
                        break;
                    case 'ELIMINADO':
                        estadoClass = 'background-color: #f8d7da; color: #721c24;';
                        estadoIcon = '❌';
                        break;
                    default:
                        estadoClass = 'background-color: #f8f9fa;';
                        estadoIcon = '❓';
                        break;
                }
                
                html += '<tr style="' + estadoClass + '">';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + semana.anio + '/' + semana.semana + '</td>';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + (semana.avance_base !== null ? semana.avance_base + '%' : '-') + '</td>';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + (semana.avance_actual !== null ? semana.avance_actual + '%' : '-') + '</td>';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + (semana.horas_base !== null ? semana.horas_base : '-') + '</td>';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + (semana.horas_actual !== null ? semana.horas_actual : '-') + '</td>';
                html += '<td style="border: 1px solid #dee2e6; padding: 6px; text-align: center; font-size: 12px;">' + estadoIcon + ' ' + semana.estado + '</td>';
                html += '</tr>';
            });
        
        html += '</tbody></table>';
        
        // Botón de regreso con mejor estilo
        html += '<div style="text-align: center; margin-top: 15px;">';
        html += '<button type="button" onclick="volverADatosActuales()" style="background-color: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">';
        html += '🔙 Volver a Datos Actuales';
        html += '</button>';
        html += '</div>';
        
        html += '</div>';
        
        // Reemplazar contenido del div flotante (no de la subconsulta)
        $('#div-flotante .tabla-interna').html(html);
        
        // Remover controles de baseline anteriores ya que ahora están en la comparación
        $('.baseline-controls').remove();
        
    }).catch(function(error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar la comparación: ' + error
        });
    });
}

// Volver a mostrar datos actuales
function volverADatosActuales() {
    console.log('🔙 VOLVER A DATOS ACTUALES - Iniciando...');
    
    // Intentar recuperar variables preservadas
    let preservedVars = $('#div-flotante').data('preserved-vars');
    
    if (preservedVars) {
        console.log('🔄 RECUPERAR VARIABLES - Variables preservadas encontradas:', preservedVars);
        
        // Restaurar variables globales
        window.idPadreOT = preservedVars.idPadreOT;
        window.codPdre = preservedVars.codPdre;
        window.columnName = preservedVars.columnName;
        window.currentIdPrograma = preservedVars.currentIdPrograma;
        window.projectCode = preservedVars.projectCode;
        window.NPdre = preservedVars.NPdre;
        window.Peso = preservedVars.Peso;
        
        console.log('✅ Variables globales restauradas');
    }
    
    // Verificar si existen las variables globales necesarias
    console.log('📋 Variables globales disponibles:', {
        idPadreOT: window.idPadreOT,
        codPdre: window.codPdre, 
        columnName: window.columnName,
        currentIdPrograma: window.currentIdPrograma
    });
    
    // Validar que existen las variables necesarias
    if (typeof window.idPadreOT !== 'undefined' && 
        typeof window.codPdre !== 'undefined' && 
        typeof window.columnName !== 'undefined') {
        
        console.log('✅ Variables globales válidas, recargando datos...');
        
        // Verificar que existe la función ListarDatos
        if (typeof ListarDatos === 'function') {
            try {
                // Recargar los datos originales
                ListarDatos(window.idPadreOT, window.codPdre, window.columnName, function(response) {
                    console.log('📊 Datos actuales recibidos:', response);
                    
                    // Verificar que existe la función ListarData
                    if (typeof ListarData === 'function') {
                        console.log('🔄 Ejecutando ListarData con', response.length, 'registros');
                        
                        // Verificar si existe la estructura de tabla en el div flotante
                        let tablaExiste = $('#div-flotante').find('.tabla-dinamica tbody').length > 0;
                        console.log('🔍 ¿Existe tabla en div-flotante?', tablaExiste);
                        
                        if (!tablaExiste) {
                            console.log('⚠️ No existe tabla, recreando estructura completa del div flotante');
                            
                            // Recrear toda la estructura del div flotante como en ProgramarSemana.php
                            let fullContent = '';
                            
                            // Crear la barra de título primero
                            let projectTitle = '';
                            if (window.projectCode && window.NPdre && window.columnName) {
                                let columnTitle = window.columnName.replace(/_/g, ' ').toUpperCase();
                                projectTitle = window.projectCode + ' - ' + window.NPdre + ' - ' + columnTitle;
                            }
                            
                            if (projectTitle) {
                                fullContent += '<div class="flotante-title" style="background: #1976d2; color: white; padding: 10px; font-weight: bold; margin-bottom: 10px; border-radius: 5px;">';
                                fullContent += projectTitle;
                                fullContent += '</div>';
                            }
                            
                            // Botones de control del div
                            fullContent += '<button id="close-btn" style="position: absolute; top: 10px; right: 10px; padding: 10px; background-color: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">';
                            fullContent += '<span class="glyphicon glyphicon-remove"></span>';
                            fullContent += '</button>';
                            fullContent += '<button id="increase-size-btn" style="position: absolute; top: 10px; right: 45px;padding: 10px; background-color: #faba52; color: white; border: none; border-radius: 5px; cursor: pointer;">';
                            fullContent += '<span class="glyphicon glyphicon-fullscreen"></span>';
                            fullContent += '</button>';
                            
                            // Botones principales
                            fullContent += '<button id="add-row-flotante-btn" style="padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">';
                            fullContent += 'Agregar Fila';
                            fullContent += '</button>';
                            fullContent += '<button id="add-Abrir-Semanas" style="padding: 10px; background-color: #b44c43; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">';
                            fullContent += 'Abrir Semanas';
                            fullContent += '</button>';
                            
                            // Contenido de la tabla
                            fullContent += '<div class="tabla-interna">';
                            fullContent += '<table id="tabla-dinamica" class="tabla-dinamica" style="width: 100%; border: 1px solid #ddd; border-collapse: collapse;">';
                            fullContent += '<thead>';
                            fullContent += '<tr><th>AÑO</th><th>SEMANA</th><th>PROG.HH</th><th>HORAS</th><th>AV. FISICO</th><th>FABRIC.</th><th style="width: 13%">Cerrar Semana</th><th style="width: 10%">Eliminar</th></tr>';
                            fullContent += '</thead>';
                            fullContent += '<tbody></tbody>';
                            fullContent += '</table>';
                            fullContent += '</div>';
                            
                            // Botón guardar
                            fullContent += '<button id="guardarDatos" style="padding: 10px; background-color: #ff8000; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">Guardar Datos</button>';
                            
                            // Reemplazar todo el contenido del div flotante
                            $('#div-flotante').html(fullContent);
                            console.log('✅ Estructura completa del div flotante recreada con barra de título');
                            
                            // Reasignar todos los eventos después de recrear la estructura
                            console.log('🔄 Reasignando eventos de botones...');
                            if (typeof initializeFloatingDivButtons === 'function') {
                                initializeFloatingDivButtons();
                            }
                            if (typeof initializeAdditionalButtons === 'function') {
                                initializeAdditionalButtons();
                            }
                            
                            // Reasignar evento del botón guardar datos manualmente
                            initializeGuardarDatosButton();
                            
                            console.log('✅ Eventos reasignados correctamente');
                        } else {
                            // Limpiar la tabla existente
                            $('#div-flotante').find('.tabla-dinamica tbody').empty();
                            console.log('🧹 Tabla limpiada');
                        }
                        
                        // Cargar los nuevos datos
                        ListarData(response);
                        
                        console.log('✅ Datos actuales restaurados exitosamente');
                        
                        // Verificar que la tabla se actualizó
                        setTimeout(function() {
                            let filasEnTabla = $('#div-flotante').find('.tabla-dinamica tbody tr').length;
                            console.log('🔍 Verificación: Filas en tabla después de restaurar:', filasEnTabla);
                            
                            if (filasEnTabla > 0) {
                                Swal.fire({
                                    title: 'Datos Restaurados',
                                    text: `Se han restaurado ${filasEnTabla} registros de programación`,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Advertencia',
                                    text: 'Los datos se restauraron pero la tabla está vacía',
                                    icon: 'warning',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        }, 200); // Dar tiempo para que la tabla se actualice
                        
                    } else {
                        console.error('❌ Función ListarData no está disponible');
                        Swal.fire('Error', 'No se pudo procesar la respuesta de datos', 'error');
                    }
                });
                
            } catch (error) {
                console.error('❌ Error al ejecutar ListarDatos:', error);
                Swal.fire('Error', 'Error al cargar los datos: ' + error.message, 'error');
            }
            
        } else {
            console.error('❌ Función ListarDatos no está disponible');
            Swal.fire('Error', 'La función para cargar datos no está disponible', 'error');
        }
        
    } else {
        console.error('❌ Variables globales no están definidas correctamente');
        console.error('Se requieren: idPadreOT, codPdre, columnName');
        
        // Intentar alternativa: cerrar el div flotante y reabrir
        Swal.fire({
            title: 'No se pueden restaurar los datos',
            text: 'Las variables necesarias no están disponibles. ¿Deseas cerrar la ventana?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Cerrar ventana',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#div-flotante').hide();
                console.log('🚪 Ventana flotante cerrada por falta de variables');
            }
        });
    }
}

// Agregar funciones al objeto global
window.verificarBaseline = verificarBaseline;
window.crearBaseline = crearBaseline;
window.obtenerComparacion = obtenerComparacion;
window.mostrarComparacionBaseline = mostrarComparacionBaseline;
window.volverADatosActuales = volverADatosActuales;
window.agregarBotonesBaseline = agregarBotonesBaseline;
window.crearBaselineManual = crearBaselineManual;

// ============================================================================
// ============================================================================
// EVENTOS DEL DIV FLOTANTE - BOTONES Y FUNCIONALIDAD
// ============================================================================

function initializeFloatingDivButtons() {
    console.log('Inicializando botones del div flotante');
    
    // Botón de cerrar
    $('#close-btn').off('click').on('click', function() {
        $('#div-flotante').hide();
        $('#div-flotante').css({
            'width': '60%',
            'height': '70%'
        });
        window.totalAvance = 0;
        console.log("Botón de cerrar clickeado");
        closeDivFlotante();
        aplicarColoresEnFilas();
        aplicarColoresEnBORDES();
    });

    // Botón de aumentar tamaño
    $('#increase-size-btn').off('click').on('click', function() {
        var div = $('#div-flotante');
        var currentWidth = div.width();
        var currentHeight = div.height();

        var newWidth = currentWidth * 1.001;
        var newHeight = currentHeight * 1.2;

        div.css({
            'width': newWidth + 'px',
            'height': newHeight + 'px'
        });
    });
}

// ============================================================================
// EVENTOS DE BOTONES DE FILA (ELIMINAR Y CERRAR)
// ============================================================================

// ============================================================================
// EVENTOS DE EDICIÓN DE CAMPOS (AVANCE, SEMANA, AÑO)
// ============================================================================

function initializeEditableFieldEvents() {
    console.log('Inicializando eventos de campos editables');
    
    // Los eventos de doble click para Avance, Semana y Año están manejados 
    // en initializeDivFlotanteEditableEvents() para evitar conflictos
    // Esta función se mantiene para compatibilidad futura
}

// ============================================================================
// FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DE VALORES
// ============================================================================

function updateAvanceValue(fila, nuevoValor) {
    var idDetalle = fila.find('.delete-row-btn').data('id-detalle');
    
    $.ajax({
        url: 'consultasAjax/actualizar_avance.php',
        method: 'POST',
        data: { 
            idDetalle: idDetalle, 
            avance: nuevoValor 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateTotalAvance();
                console.log('Avance actualizado exitosamente');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar avance',
                    text: response.message
                });
            }
        },
        error: function() {
            console.error('Error al actualizar avance');
        }
    });
}

function updateSemanaValue(fila, nuevoValor) {
    var idDetalle = fila.find('.delete-row-btn').data('id-detalle');
    
    $.ajax({
        url: 'consultasAjax/actualizar_semana.php',
        method: 'POST',
        data: { 
            idDetalle: idDetalle, 
            semana: nuevoValor 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Semana actualizada exitosamente');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar semana',
                    text: response.message
                });
            }
        },
        error: function() {
            console.error('Error al actualizar semana');
        }
    });
}

function updateAnioValue(fila, nuevoValor) {
    var idDetalle = fila.find('.delete-row-btn').data('id-detalle');
    
    $.ajax({
        url: 'consultasAjax/actualizar_anio.php',
        method: 'POST',
        data: { 
            idDetalle: idDetalle, 
            anio: nuevoValor 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Año actualizado exitosamente');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar año',
                    text: response.message
                });
            }
        },
        error: function() {
            console.error('Error al actualizar año');
        }
    });
}

// ============================================================================
// FUNCIONES DE UTILIDAD PARA GESTIÓN DE DATOS
// ============================================================================

function getTotalAvanceExistente() {
    var total = 0;
    // Cubre filas guardadas (texto) Y filas nuevas (input) en la tabla flotante
    var $tbody = $('#div-flotante .tabla-dinamica tbody');
    if ($tbody.length === 0) $tbody = $('#tabla-dinamica tbody');
    $tbody.find('tr').each(function() {
        var $row = $(this);
        var $inp = $row.find('input.Avance');
        if ($inp.length > 0) {
            total += parseFloat($inp.val()) || 0;
        } else {
            var avText = $row.find('.editable-avance').text()
                       || $row.find('td[data-field="Avance"]').text();
            total += parseFloat(avText.replace('%', '').trim()) || 0;
        }
    });
    return total;
}

function refreshTable() {
    if (typeof subQueryTable !== 'undefined' && subQueryTable) {
        subQueryTable.ajax.reload(null, false);
    }
}

function updateTotalAvance() {
    var total = getTotalAvanceExistente();
    window.totalAvance = total;
    
    // Actualizar display del total si existe
    var totalDisplay = $('#total-avance-display');
    if (totalDisplay.length) {
        totalDisplay.text('Total Avance: ' + total.toFixed(2));
    }
    
    console.log('Total avance actualizado:', total);
}

// Recalcula y establece el 'max' para inputs de AvanceReal (no obliga a llegar a 100%, solo evita pasar)
function updateAvanceRealInputsMax($currentInput) {
    var total = 0;
    var inputs = [];

    // Tabla principal
    $('#tabla-dinamica tbody tr').each(function() {
        var $row = $(this);
        var $cell = $row.find('.editable[data-field="AvanceReal"]');
        if ($cell.length) {
            var $inp = $cell.find('input.edit-input');
            if ($inp.length) inputs.push($inp);
            else total += parseFloat($cell.text().replace('%', '').trim()) || 0;
        }
    });

    // Div flotante
    $('#div-flotante .tabla-dinamica tbody tr').each(function() {
        var $row = $(this);
        var $cell = $row.find('.editable[data-field="AvanceReal"]');
        if ($cell.length) {
            var $inp = $cell.find('input.edit-input');
            if ($inp.length) inputs.push($inp);
            else total += parseFloat($cell.text().replace('%', '').trim()) || 0;
        }
    });

    // Sumar valores de inputs excepto el actual
    inputs.forEach(function($inp) {
        if ($currentInput && $currentInput.is($inp)) return;
        total += parseFloat($inp.val()) || 0;
    });

    var remaining = Math.max(0, 100 - total);

    if ($currentInput && $currentInput.length) {
        var current = parseFloat($currentInput.val()) || 0;
        $currentInput.attr('max', (current + remaining).toFixed(2));
    }
}

// Hacer todas las nuevas funciones disponibles globalmente
window.initializeDoubleClickEvents = initializeDoubleClickEvents;
window.initializeFloatingDivButtons = initializeFloatingDivButtons;
window.initializeEditableFieldEvents = initializeEditableFieldEvents;
window.updateAvanceValue = updateAvanceValue;
window.updateSemanaValue = updateSemanaValue;
window.updateAnioValue = updateAnioValue;
window.getTotalAvanceExistente = getTotalAvanceExistente;
window.refreshTable = refreshTable;
window.updateTotalAvance = updateTotalAvance;

// ============================================================================
// EVENTOS DE BOTONES ADICIONALES
// ============================================================================

function initializeAdditionalButtons() {
    console.log('Inicializando botones adicionales');
    
    // Botón para agregar nueva fila al div flotante
    $('#add-row-flotante-btn').off('click').on('click', function() {
        var totalAvanceCargado = getTotalAvanceExistente();
        var remainingAvance = 100 - totalAvanceCargado;

        if (remainingAvance <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite alcanzado',
                text: 'El total de los avances ya alcanza o supera el 100%. No se pueden agregar más filas.'
            });
            return;
        }

        var newRowData = {
            Peso: window.Peso,
            codPdre: window.codPdre,
            projectCode: window.projectCode,
            idPadreOT: window.idPadreOT,
            Proceso: window.columnName
        };

        insertRowInFlotanteTable(newRowData, remainingAvance);
        updateTotalAvance();
    });

    // Botón para abrir semanas
    $('#add-Abrir-Semanas').off('click').on('click', function() {
        console.log('Semanas seleccionadas para abrir:', window.abrirSemanas);
        
        if (typeof window.abrirSemanas === 'undefined' || window.abrirSemanas.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Selecciona semanas',
                html: 'No se han seleccionado semanas para abrir.<br><br><strong>Para seleccionar semanas:</strong><br>1. Haz click en los botones "Cerrado" (grises) de las semanas que quieres abrir<br>2. Los botones seleccionados cambiarán de color<br>3. Luego presiona este botón para abrir todas las semanas seleccionadas',
                width: 500
            });
            return;
        }
        var confirmMessage = '¿Estás seguro de que quieres abrir ' + window.abrirSemanas.length + ' semana(s) seleccionada(s)?';
        Swal.fire({
            title: '¿Abrir semanas?',
            text: confirmMessage,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, abrir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var semanasProcesadas = 0;
                var totalSemanas = window.abrirSemanas.length;
                var errores = [];
                var warningsAcc = [];

                // Procesar cada semana seleccionada
                window.abrirSemanas.forEach(function(idPrograma) {
                    $.ajax({
                        url: 'consultasAjax/actualizar_estado.php',
                        method: 'POST',
                        data: {
                            idPrograma: idPrograma,
                            estado: 1
                },
                dataType: 'json',
                success: function(response) {
                    semanasProcesadas++;
                    
                    if (response.success) {
                        // Actualizar visualmente la fila
                        $('#div-flotante tbody tr').each(function() {
                            var rowIdPrograma = $(this).find('td[data-field="IdPrograma"]').text();
                            if (rowIdPrograma == idPrograma) {
                                $(this).find('td[data-field="Estado"]').text('1');
                                $(this).find('.cerrar-row-btn').text('Abierto').css('background-color', '#5ef518');
                            }
                        });
                        if (response.warnings && Array.isArray(response.warnings) && response.warnings.length > 0) {
                            warningsAcc = warningsAcc.concat(response.warnings);
                        }
                        console.log('Semana abierta exitosamente:', idPrograma);
                    } else {
                        errores.push('ID ' + idPrograma + ': ' + response.message);
                    }

                    // Verificar si todas las semanas han sido procesadas
                    if (semanasProcesadas === totalSemanas) {
                        finalizarAperturaSemanas(errores, warningsAcc);
                    }
                },
                error: function(xhr, status, error) {
                    semanasProcesadas++;
                    errores.push('ID ' + idPrograma + ': Error de comunicación');
                    console.error('Error al abrir semana:', idPrograma, error);

                    // Verificar si todas las semanas han sido procesadas
                    if (semanasProcesadas === totalSemanas) {
                        finalizarAperturaSemanas(errores, warningsAcc);
                    }
                }
            });
        });
            }
        });
    });

    function finalizarAperturaSemanas(errores, warningsAcc) {
        // Limpiar la lista de semanas seleccionadas
        window.abrirSemanas = [];
        
        // Mostrar resultado
        if (errores.length === 0) {
            Swal.fire({
                icon: 'success',
                title: 'Semanas abiertas',
                text: 'Todas las semanas se abrieron correctamente.'
            });
        } else {
            var mensaje = 'Proceso completado con algunos errores:\n\n' + errores.join('\n');
            Swal.fire({
                icon: 'warning',
                title: 'Proceso completado con errores',
                text: mensaje,
                width: 500
            });
        }

        // Mostrar advertencias si las hay
        if (warningsAcc && warningsAcc.length > 0) {
            var html = '';
            warningsAcc.forEach(function(w, i) {
                html += '<div style="text-align:left;margin-bottom:8px;">' + (i+1) + '. ' + (w.message || '') + '</div>';
            });
            Swal.fire({
                icon: 'warning',
                title: 'Advertencias',
                html: html,
                width: 600
            });
        }

        // Recargar los datos para reflejar los cambios
        if (typeof window.idPadreOT !== 'undefined' && typeof window.codPdre !== 'undefined' && typeof window.columnName !== 'undefined') {
            ListarDatos(window.idPadreOT, window.codPdre, window.columnName, function(response) {
                ListarData(response);
            });
        }
    }

    // Botón guardar datos
    $('#guardarDatos').off('click').on('click', function() {
        const filas = $('#div-flotante .tabla-dinamica tbody tr');
        console.log('Número de filas:', filas.length);
        let datosTabla = [];
        var sumatotal = 0;
        
        filas.each(function(index) {
            var $row = $(this);
            
            // Ignorar filas de placeholder que dicen "No se encontraron datos." o filas con una sola celda colspan
            var $tds = $row.find('td');
            if ($tds.length === 1) {
                var tdText = $tds.text().trim();
                var colspan = $tds.attr('colspan');
                if ((colspan && parseInt(colspan) > 1) || tdText.toLowerCase().includes('no se encontraron')) {
                    console.log('Saltando fila placeholder', index, tdText);
                    return; // continuar al siguiente tr
                }
            }
            
            var anio = '';
            var semana = '';
            var porcentaje1 = 0;
            var totalHoras = '';
            
            // Caso 1: fila nueva creada por insertRowInFlotanteTable (selects + inputs)
            if ($row.find('select.Aniio, #Aniio').length > 0 || $row.find('select.Semana, #Semana').length > 0) {
                anio = ($row.find('select.Aniio, #Aniio').val() || '').toString().trim();
                semana = ($row.find('select.Semana, #Semana').val() || '').toString().trim();
                // Fallback: si el value está vacío, intentar obtener el texto de la opción seleccionada
                if (!semana) {
                    var optText = $row.find('select.Semana option:selected, #Semana option:selected').text() || '';
                    semana = optText.toString().trim();
                }
                var $av = $row.find('input.Avance');
                porcentaje1 = $av.length > 0 ? (parseFloat($av.val()) || 0) : 0;
                var $th = $row.find('input.TotalHoras, .TotalHoras');
                totalHoras = $th.length > 0 ? $th.val() : ($row.find('.TotalHoras').val() || '');
            } else if ($row.find('td[data-field="Aniio"]').length > 0) {
                // Caso 2: fila existente renderizada por ListarData (td[data-field])
                anio = $row.find('td[data-field="Aniio"]').text().trim() || '';
                semana = $row.find('td[data-field="Semana"]').text().trim() || '';
                // Avance puede ser input (editable) o texto con '%'
                var $avInput = $row.find('input');
                if ($avInput.length > 0) {
                    porcentaje1 = parseFloat($avInput.val()) || 0;
                } else {
                    var txtAv = $row.find('td[data-field="Avance"]').text() || '';
                    txtAv = txtAv.replace(/[^0-9\.\-]/g, '');
                    porcentaje1 = parseFloat(txtAv) || 0;
                }
                totalHoras = $row.find('td[data-field="TotHoras"]').text().trim() || ($row.find('.TotalHoras').val() || '');
            } else {
                // Fallback por índices (compatibilidad)
                var celdas = $row.find('td');
                anio = $(celdas[0]).find('select').val() || $(celdas[0]).text().trim() || '';
                semana = $(celdas[1]).find('select').val() || $(celdas[1]).text().trim() || '';
                var $avF = $(celdas[2]).find('input');
                if ($avF.length > 0) porcentaje1 = parseFloat($avF.val()) || 0;
                else porcentaje1 = parseFloat($(celdas[2]).text().replace(/[^0-9\.\-]/g, '')) || 0;
                var $thF = $(celdas[3]).find('input');
                totalHoras = $thF.length > 0 ? $thF.val() : $(celdas[3]).text().trim();
            }

            // Normalizar porcentaje y sumar
            if (typeof porcentaje1 === 'string') {
                porcentaje1 = parseFloat(porcentaje1.replace(/[^0-9\.\-]/g, '')) || 0;
            }
            // Asegurar rango válido [0..100]
            porcentaje1 = Math.max(0, Math.min(100, porcentaje1));
            
            console.log('Fila procesada ->', { anio: anio, semana: semana, porcentaje: porcentaje1, totalHoras: totalHoras });
            sumatotal += porcentaje1;
            
            // Capturar valor de fabricacion
            var fabricacion = 'tama'; // valor por defecto
            var $selectFabricacion = $row.find('select.select-fabricacion, select[data-field="fabricacion"]');
            console.log('Select fabricacion encontrado:', $selectFabricacion.length, 'Valor:', $selectFabricacion.val());
            if ($selectFabricacion.length > 0) {
                fabricacion = $selectFabricacion.val() || 'tama';
            }
            console.log('Valor final de fabricacion:', fabricacion);
            
            const datosFila = {
                anio: anio,
                semana: semana,
                porcentaje: porcentaje1,
                totalHoras: totalHoras,
                fabricacion: fabricacion,
                proyecto: window.projectCode || projectCode1,
                codPdre: window.codPdre || codPdre1,
                idPadreOT: window.idPadreOT || idPadreOT1,
                proceso: window.columnName || Proceso1
            };

            datosTabla.push(datosFila);
        });

        // Procesar los datos
        processTableData(datosTabla, sumatotal);
    });
}

function insertRowInFlotanteTable(newRowData, remainingAvance) {
    var currentYear = new Date().getFullYear();
    var tableBody = $('#div-flotante').find('.tabla-dinamica tbody');

    var rowHtml = '<tr>';
    rowHtml += '<td><select class="Aniio" style="text-align: center;width: 100%; padding: 1px; font-size: 13px; border-radius: 9px;">';
    
    // Aquí necesitarías acceso a la variable allanio, podrías pasarla como parámetro global
    if (typeof window.allanio !== 'undefined') {
        window.allanio.forEach(function(item) {
            var selected = (item.anio == currentYear) ? 'selected' : '';
            rowHtml += '<option value="' + item.anio + '" ' + selected + '>' + item.anio + '</option>';
        });
    }
    rowHtml += '</select></td>';

    rowHtml += '<td><select class="Semana" style="text-align: center;width: 100%; padding: 1px; font-size: 13px; border-radius: 9px;">';
    if (typeof window.allSemanas !== 'undefined') {
        // Normalizar allSemanas: puede ser un array plano o un objeto mapeado por año
        var weeks = [];
        if (Array.isArray(window.allSemanas)) {
            weeks = window.allSemanas;
        } else if (window.allSemanas && typeof window.allSemanas === 'object') {
            // Si es un mapeo por año, intentar usar el año actual o el primero disponible
            var keyYear = String(currentYear);
            if (window.allanio && Array.isArray(window.allanio) && window.allanio.length) {
                keyYear = (window.allanio.indexOf(String(currentYear)) !== -1) ? String(currentYear) : String(window.allanio[0]);
            }
            if (window.allSemanas[keyYear]) {
                weeks = window.allSemanas[keyYear];
            } else {
                // Aplanar valores por si vienen agrupados
                weeks = Object.values(window.allSemanas).reduce(function(acc, v) {
                    if (Array.isArray(v)) return acc.concat(v);
                    if (v && typeof v === 'object') return acc.concat(Object.values(v));
                    return acc.concat(v);
                }, []);
            }
        }
        weeks = weeks || [];
        if (Array.isArray(weeks)) {
            weeks.forEach(function(item) {
                // item puede ser objeto con {semana:...} o un valor primitivo
                var val = (item && typeof item === 'object' && 'semana' in item) ? item.semana : item;
                rowHtml += '<option value="' + val + '">' + val + '</option>';
            });
        }
    }
    rowHtml += '</select></td>';

    rowHtml += '<td data-field="Avance"><input type="number" class="form-control Avance" name="Avance" value="" style="text-align: center;" min="0" max="' + remainingAvance.toFixed(2) + '" step="0.01"></td>';
    rowHtml += '<td data-field="TotHoras"><input type="text" class="form-control TotalHoras" name="TotHoras" value="" style="text-align: center;" readonly></td>';
    rowHtml += '<td data-field="AvanceReal"><input type="number" class="form-control AvanceReal" name="AvanceReal" value="0" style="text-align: center; width: 60px;" min="0" max="100">%</td>';
    
    // Generar select de fabricacion - para procesos especiales solo mostrar svt-especiales
    var currentProcess = window.columnName || '';
    var isProcesoEspecial = ['MO050', 'MO060', 'MO070', 'MO080'].includes(currentProcess);
    
    rowHtml += '<td data-field="fabricacion"><select class="select-fabricacion" data-field="fabricacion" style="padding: 3px; border-radius: 4px; border: 1px solid #ccc;">';
    if (isProcesoEspecial) {
        rowHtml += '<option value="svt-especiales" selected>SVT-ESPECIALES</option>';
    } else {
        rowHtml += '<option value="tama" selected>TAMA</option>';
        rowHtml += '<option value="svt">SVT</option>';
    }
    rowHtml += '</select></td>';
    rowHtml += '<td><button type="button" class="btn btn-danger delete-row-btn">Eliminar</button></td>';
    rowHtml += '</tr>';

    tableBody.append(rowHtml);
    
    // Agregar eventos para la nueva fila
    setupNewRowEvents();
}

function setupNewRowEvents() {
    // Evento para eliminar fila
    $('#div-flotante').find('tr:last-child .delete-row-btn').off('click').on('click', function() {
        $(this).closest('tr').remove();
        updateTotalAvance();
    });

    // Evento para cambio de porcentaje (con validación: suma total no puede superar 100%)
    $('#div-flotante').find('tr:last-child').find('.Avance').off('input').on('input', function() {
        var $thisInput = $(this);
        var porcentajeNuevo = parseFloat($thisInput.val()) || 0;
        // Calcular total de OTRAS filas (sin contar la fila actual)
        var totalOtros = 0;
        var $tbody = $('#div-flotante .tabla-dinamica tbody');
        if ($tbody.length === 0) $tbody = $('#tabla-dinamica tbody');
        $tbody.find('tr').each(function() {
            var $r = $(this);
            var $inp = $r.find('input.Avance');
            if ($inp.length > 0 && $inp[0] !== $thisInput[0]) {
                totalOtros += parseFloat($inp.val()) || 0;
            } else if ($inp.length === 0) {
                var avText = $r.find('.editable-avance').text()
                           || $r.find('td[data-field="Avance"]').text();
                totalOtros += parseFloat(avText.replace('%', '').trim()) || 0;
            }
        });
        var maxPermitido = parseFloat((100 - totalOtros).toFixed(2));
        if (maxPermitido < 0) maxPermitido = 0;
        if (porcentajeNuevo > maxPermitido) {
            $thisInput.val(maxPermitido);
            porcentajeNuevo = maxPermitido;
        }
        // Actualizar max del input para que el navegador también valide
        $thisInput.attr('max', maxPermitido);
        var totalHoras = (porcentajeNuevo / 100) * window.Peso;
        $thisInput.closest('tr').find('.TotalHoras').val(totalHoras.toFixed(2));
        updateTotalAvance();
    });

    // Eventos para selects de año y semana
    // Cuando cambia el año, propagar el año y repoblar las opciones de semana según el año seleccionado
    $('#div-flotante').off('change', '.Aniio').on('change', '.Aniio', function() {
        var selectedYear = $(this).val();
        // Establecer el mismo año en todos los selects Aniio (comportamiento previo)
        $('#div-flotante').find('.Aniio').each(function() {
            $(this).val(selectedYear);
        });
    });
    
    $('#div-flotante').off('change', '.Semana').on('change', '.Semana', function() {
        var selectedSemana = parseInt($(this).val());
        var nextRows = $(this).closest('tr').nextAll();

        nextRows.each(function(index) {
            var nextSemana = selectedSemana + index + 1;
            if (nextSemana <= 52) {
                $(this).find('.Semana').val(nextSemana);
            }
        });
    });
}

function processTableData(datosTabla, sumatotal) {
    console.log('Procesando datos de la tabla:', datosTabla);
    console.log('Suma total:', sumatotal);
    
    // Validar que hay datos para enviar
    if (!datosTabla || datosTabla.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay datos para guardar'
        });
        return;
    }
    
    // Validar que el total sea 100% (con tolerancia de 0.01)
    var tolerance = 0.01;
    if (Math.abs(sumatotal - 100) > tolerance) {
        Swal.fire({
            icon: 'warning',
            title: 'Total incorrecto',
            text: 'El total de avances debe ser 100%. Actualmente es: ' + sumatotal.toFixed(2) + '%'
        });
        return;
    }
    
    // Validar que todas las filas tengan año y semana
    for (var i = 0; i < datosTabla.length; i++) {
        var fila = datosTabla[i];
        if (!fila.anio || fila.anio === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Fila ' + (i + 1) + ' no tiene Año seleccionado.'
            });
            return;
        }
        if (!fila.semana || fila.semana === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Fila ' + (i + 1) + ' no tiene Semana seleccionada.'
            });
            return;
        }
        // Validar campos obligatorios del contexto
        if (!fila.proyecto || fila.proyecto === '' || fila.proyecto === 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de contexto',
                html: 'No se ha establecido el proyecto para la fila ' + (i + 1) + '.<br><br>Por favor, cierra este panel y vuelve a hacer clic derecho en la celda de la tabla principal.'
            });
            console.error('Fila sin proyecto:', fila);
            return;
        }
        if (!fila.idPadreOT || fila.idPadreOT === '' || fila.idPadreOT === 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de contexto',
                html: 'No se ha establecido el ID del padre para la fila ' + (i + 1) + '.<br><br>Por favor, cierra este panel y vuelve a hacer clic derecho en la celda de la tabla principal.'
            });
            console.error('Fila sin idPadreOT:', fila);
            return;
        }
        if (!fila.proceso || fila.proceso === '' || fila.proceso === 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de contexto',
                html: 'No se ha establecido el proceso para la fila ' + (i + 1) + '.<br><br>Por favor, cierra este panel y vuelve a hacer clic derecho en la celda de la tabla principal.'
            });
            console.error('Fila sin proceso:', fila);
            return;
        }
        if (!fila.codPdre || fila.codPdre === '' || fila.codPdre === 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de contexto',
                html: 'No se ha establecido el código padre para la fila ' + (i + 1) + '.<br><br>Por favor, cierra este panel y vuelve a hacer clic derecho en la celda de la tabla principal.'
            });
            console.error('Fila sin codPdre:', fila);
            return;
        }
    }
    
    // Debug: mostrar datos que se enviarán al servidor
    console.log('📤 Datos a enviar al servidor:', datosTabla);
    console.log('📤 Variables globales:', {
        projectCode: window.projectCode,
        codPdre: window.codPdre,
        idPadreOT: window.idPadreOT,
        columnName: window.columnName,
        projectCode1: typeof projectCode1 !== 'undefined' ? projectCode1 : 'undefined',
        codPdre1: typeof codPdre1 !== 'undefined' ? codPdre1 : 'undefined',
        idPadreOT1: typeof idPadreOT1 !== 'undefined' ? idPadreOT1 : 'undefined',
        Proceso1: typeof Proceso1 !== 'undefined' ? Proceso1 : 'undefined'
    });
    
    // Enviar datos usando la misma estructura que acabado.js
    $.ajax({
        url: 'consultasAjax1/consulta3.php',
        method: 'POST',
        data: {
            datos: JSON.stringify(datosTabla)
        },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Datos guardados exitosamente',
                    text: response.message + ' Puedes continuar editando en esta ventana.',
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // ============================================================================
                // BASELINE: Crear automáticamente después de insertar datos
                // ============================================================================
                console.log('🔍 BASELINE - Datos guardados exitosamente, verificando baseline...');
                
                // Obtener IdPrograma de los datos insertados (pueden ser múltiples del mismo programa)
                if (response.inserted && response.inserted.length > 0) {
                    // Obtener IDs únicos de programas insertados
                    let idsPrograma = [...new Set(response.inserted.map(item => item.IdPrograma))];
                    console.log('🔍 BASELINE - IDs de programas insertados (únicos):', idsPrograma);
                    
                    // Para cada programa único, verificar y crear baseline si es necesario
                    idsPrograma.forEach(function(idPrograma) {
                        if (idPrograma) {
                            console.log('🔍 BASELINE - Procesando programa:', idPrograma);
                            
                            // Verificar si ya tiene baseline
                            verificarBaseline(idPrograma).then(function(resultado) {
                                console.log('🔍 BASELINE - Resultado verificación post-inserción para', idPrograma, ':', resultado);
                                if (!resultado.tiene_baseline) {
                                    console.log('✅ BASELINE - Programa sin baseline detectado:', idPrograma);
                                    
                                    // VERIFICACIÓN ADICIONAL: Verificar nuevamente antes de crear
                                    verificarBaseline(idPrograma).then(function(verificacionFinal) {
                                        console.log('🔍 BASELINE - Verificación final antes de crear:', verificacionFinal);
                                        
                                        if (!verificacionFinal.tiene_baseline) {
                                            console.log('✅ BASELINE - Confirmado: Creando baseline para nuevo programa:', idPrograma);
                                            
                                            // Solo UN intento de creación, sin delays complejos
                                            crearBaseline(idPrograma).then(function(baselineResponse) {
                                                console.log('✅ BASELINE - Baseline creado exitosamente para', idPrograma, ':', baselineResponse.message);
                                                
                                                // ACTUALIZAR BOTONES después de crear baseline exitosamente
                                                console.log('🔄 BASELINE - Actualizando botones después de crear baseline...');
                                                agregarBotonesBaseline(idPrograma, true); // true = con baseline
                                                console.log('✅ BASELINE - Botones actualizados a modo comparación');
                                                
                                            }).catch(function(error) {
                                                console.warn('⚠️ BASELINE - Error creando baseline para', idPrograma, ':', error);
                                                // No hacer segundo intento - evitar duplicados
                                            });
                                        } else {
                                            console.log('🚫 BASELINE - Ya existe baseline (verificación final), no se creará duplicado');
                                        }
                                    }).catch(function(errorVerificacion) {
                                        console.error('❌ BASELINE - Error en verificación final:', errorVerificacion);
                                    });
                                } else {
                                    console.log('🚫 BASELINE - Programa ya tiene baseline, no se creará duplicado');
                                }
                            }).catch(function(error) {
                                console.error('❌ BASELINE - Error verificando baseline post-inserción para', idPrograma, ':', error);
                            });
                        }
                    });
                }
                
                // NO cerrar el div flotante - mantenerlo abierto para continuar trabajando
                // $('#div-flotante').hide(); // COMENTADO: Para mantener el div abierto
                
                // Recargar los datos para mostrar los cambios guardados
                if (typeof ListarDatos === 'function' && typeof ListarData === 'function') {
                    console.log('🔄 Recargando datos en div flotante después de guardar...');
                    ListarDatos(window.idPadreOT || idPadreOT1, window.codPdre || codPdre1, window.columnName || Proceso1, function(response2) {
                        ListarData(response2);
                        console.log('✅ Datos recargados en div flotante');
                    });
                }
            } else {
                var errorHtml = response.message;
                if (response.errors && response.errors.length > 0) {
                    console.error('Errores detallados:', response.errors);
                    errorHtml += '<br><br><strong>Detalles:</strong><ul style="text-align:left; margin-top:10px;">';
                    response.errors.forEach(function(err) {
                        errorHtml += '<li>' + err + '</li>';
                    });
                    errorHtml += '</ul>';
                }
                if (response.skipped && response.skipped.length > 0) {
                    console.warn('Filas omitidas:', response.skipped);
                    errorHtml += '<br><strong>Filas omitidas (duplicadas):</strong><ul style="text-align:left; margin-top:10px;">';
                    response.skipped.forEach(function(skip) {
                        errorHtml += '<li>' + skip + '</li>';
                    });
                    errorHtml += '</ul>';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    html: errorHtml
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', {xhr, status, error});
            console.error('Respuesta del servidor:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'Error de conexión al guardar los datos. Ver consola para más detalles.'
            });
        }
    });
}

function actualizarEstado(idPrograma, nuevoEstado) {
    console.log('Actualizando estado:', { idPrograma, nuevoEstado });
    
    $.ajax({
        url: 'consultasAjax/actualizar_estado.php',
        method: 'POST',
        data: {
            idPrograma: idPrograma,
            estado: nuevoEstado
        },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta actualizar estado:', response);
            if (response.success) {
                console.log('Estado actualizado exitosamente:', response.message);
            } else {
                console.error('Error al actualizar estado:', response.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar estado',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al actualizar estado:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'Error de comunicación al actualizar estado'
            });
        }
    });
}

// Hacer las nuevas funciones disponibles globalmente
window.initializeAdditionalButtons = initializeAdditionalButtons;
window.insertRowInFlotanteTable = insertRowInFlotanteTable;
window.setupNewRowEvents = setupNewRowEvents;
window.processTableData = processTableData;
window.actualizarEstado = actualizarEstado;

// ============================================================================
// EVENTOS ADICIONALES DEL DIV FLOTANTE - MIGRADOS DESDE PHP
// ============================================================================

function initializeDivFlotanteEditableEvents() {
    console.log('Inicializando eventos editables del div flotante');
    
    // Evento de eliminar fila
    $('#div-flotante').off('click', '.delete-row-btn').on('click', '.delete-row-btn', function() {
        var estado = $(this).closest('tr').find('td[data-field="Estado"]').text();
        if (estado === '1') {
            EliminarRow(this);  
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'Esta cerrado la semana'
            });
        }
    });

    // Evento de doble click en Avance (usando delegación)
    $('#div-flotante').off('dblclick', '.editable[data-field="Avance"]').on('dblclick', '.editable[data-field="Avance"]', function() {   
        console.log('=== DOBLE CLICK EN AVANCE DETECTADO ===');
        console.log('Elemento clickeado:', this);
        console.log('Clases:', $(this).attr('class'));
        console.log('Data-field:', $(this).attr('data-field'));
        
        var estado = $(this).closest('tr').find('td[data-field="Estado"]').text();
        console.log('Estado de la fila:', estado);
        
        if (estado === '1') {
            console.log('Estado válido, llamando handleEditAvance');
            handleEditAvance(this);
        } else {
            console.log('Estado inválido:', estado);
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'Esta cerrado la semana'
            });
        }
    });

    // Evento de doble click en Avance Real (mismas restricciones que Avance)
    $('#div-flotante').off('dblclick', '.editable[data-field="AvanceReal"]').on('dblclick', '.editable[data-field="AvanceReal"]', function() {
        var estado = $(this).closest('tr').find('td[data-field="Estado"]').text();
        if (estado !== '1') {
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'No se puede editar el avance real porque la semana está cerrada'
            });
            return;
        }

        var cell = $(this);
        var row = cell.closest('tr');
        var currentValue = parseFloat(cell.text().replace('%', '').trim()) || 0;
        cell.html('<input type="number" class="edit-input" min="0" step="0.01" value="' + currentValue + '">');
        var inputField = cell.find('input.edit-input');
        inputField.focus();

        // Inicializar max dinámico para AvanceReal (no obliga a 100%, solo evita pasar)
        updateAvanceRealInputsMax(inputField);

        inputField.on('input', function() {
            updateAvanceRealInputsMax($(this));
        });

        inputField.on('blur', function() {
            var newValue = parseFloat($(this).val()) || 0;
            if (newValue < 0) newValue = 0;
            var maxAllowed = parseFloat($(this).attr('max')) || 100;

            if (newValue > maxAllowed) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite excedido',
                    text: 'El avance real no puede superar ' + maxAllowed.toFixed(2) + '%. Se ajustará al máximo disponible.'
                });
                newValue = maxAllowed;
            }

            if (newValue !== currentValue) {
                cell.html(newValue.toFixed(2) + '%');
                var idPrograma = row.find('td[data-field="IdPrograma"]').text();
                updateRecord(idPrograma, 'AvanceReal', newValue);
            } else {
                cell.html(currentValue.toFixed(2) + '%');
            }

            // Recalcular máximos en caso de cambio
            updateAvanceRealInputsMax();
        });
    });

    // Evento de doble click en Semana
    $(document).off('dblclick', '.editable[data-field="Semana"]').on('dblclick', '.editable[data-field="Semana"]', function() {  
        var estado = $(this).closest('tr').find('td[data-field="Estado"]').text();
        if (estado === '1') {
            handleEditSemana(this);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'Esta cerrado la semana'
            });
        }
    });

    // Evento de doble click en Año
    $(document).off('dblclick', '.editable[data-field="Aniio"]').on('dblclick', '.editable[data-field="Aniio"]', function() {  
        var estado = $(this).closest('tr').find('td[data-field="Estado"]').text();
        if (estado === '1') {
            handleEditAniio(this);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Semana cerrada',
                text: 'Esta cerrado la semana'
            });
        }
    });

    // Evento para el botón cerrar semana (versión adicional)
    $('#div-flotante').off('click', '.cerrar-row-btn-individual').on('click', '.cerrar-row-btn-individual', function() {
        var fila = $(this).closest('tr');
        var idPrograma = fila.find('td[data-field="IdPrograma"]').text();
        var estado = fila.find('td[data-field="Estado"]').text();
        
        // Solo validar si está abierto (estado 1) y se quiere cerrar
        if (estado === '1') {
            // Contar cuántas semanas abiertas (Estado = 1 o filas nuevas) hay en la tabla
            var semanasAbiertas = 0;
            $('#div-flotante .tabla-dinamica tbody tr').each(function() {
                var $tr = $(this);
                var $tds = $tr.find('td');
                if ($tds.length === 1) {
                    var tdText = $tds.text().trim();
                    var colspan = $tds.attr('colspan');
                    if ((colspan && parseInt(colspan) > 1) || tdText.toLowerCase().includes('no se encontraron')) {
                        return;
                    }
                }
                var $estadoField = $tr.find('[data-field="Estado"]');
                var estadoFila = $estadoField.length > 0 ? $estadoField.text().trim() : '';
                if (estadoFila === '1' || estadoFila === '') {
                    semanasAbiertas++;
                }
            });
            
            // Solo exigir 100% si esta es la última semana abierta
            if (semanasAbiertas <= 1) {
                var sumaAvanceReal = calcularSumaAvanceReal();
                var tolerance = 0.01;
                
                if (Math.abs(sumaAvanceReal - 100) > tolerance) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se puede cerrar la última semana',
                        html: 'La suma de los porcentajes de <strong>Avance Físico (Real)</strong> debe ser <strong>100%</strong> para cerrar la última semana.<br><br>' +
                              'Suma actual: <strong>' + sumaAvanceReal.toFixed(2) + '%</strong><br><br>' +
                              'Por favor, ajusta los porcentajes de avance físico antes de cerrar.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
            }
        }
        
        var nuevoEstado = '2';

        $(this).text('Cerrado').css('background-color', '#95a5a6');
        fila.find('td[data-field="Estado"]').text(nuevoEstado);
        
        actualizarEstado(idPrograma, nuevoEstado);
        refreshTable();
    });
    
    // DEBUG: Evento de prueba para verificar que los elementos existen
    $('#div-flotante').on('click', '.editable[data-field="Avance"]', function() {
        console.log('=== CLICK SIMPLE EN AVANCE ===');
        console.log('Elemento:', this);
        console.log('Texto:', $(this).text());
        console.log('Clases:', $(this).attr('class'));
        console.log('Data-field:', $(this).attr('data-field'));
    });
}

// ============================================================================
// FUNCIONES DE EDICIÓN MIGRADAS DESDE PHP
// ============================================================================

function handleEditAvance(cell) {
    console.log('=== HANDLE EDIT AVANCE INICIADO ===');
    console.log('Cell recibida:', cell);
    console.log('Texto actual:', $(cell).text());
    
    var row = $(cell).closest('tr');
    var currentAvance = parseFloat($(cell).text().replace('%', '').trim()) || 0;
    var totalAvancesPrevios = 0;

    console.log('Avance actual:', currentAvance);

    // Sumar los avances actuales (filas guardadas de texto Y filas nuevas con input)
    var $tbodyEdit = $('#div-flotante .tabla-dinamica tbody');
    if ($tbodyEdit.length === 0) $tbodyEdit = $('#tabla-dinamica tbody');
    $tbodyEdit.find('tr').each(function() {
        var $r = $(this);
        var $inp = $r.find('input.Avance');
        if ($inp.length > 0) {
            totalAvancesPrevios += parseFloat($inp.val()) || 0;
        } else {
            totalAvancesPrevios += parseFloat($r.find('.editable[data-field="Avance"]').text().replace('%', '').trim()) || 0;
        }
    });

    // Calcular cuánto avance queda disponible para esta fila
    var avanceMaximo = 100 - totalAvancesPrevios + currentAvance;

    if (avanceMaximo <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Avance completo',
            text: 'No se puede editar el avance. El total ya ha alcanzado el 100%.'
        });
        return;
    }

    // Hacer editable el campo de "Avance"
    var currentValue = $(cell).text();
    $(cell).html('<input type="number" class="edit-input" min="0" max="' + avanceMaximo.toFixed(2) + '" step="0.01" value="' + currentAvance + '">');
    var inputField = $(cell).find('input');
    inputField.focus();

    // Detectar cuando el usuario termine de editar
    inputField.on('blur', function() {
        var newAvance = parseFloat($(this).val()) || 0;

        // Validar que el nuevo avance no exceda el máximo permitido
        if (newAvance > avanceMaximo) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite excedido',
                text: 'El avance no puede exceder ' + avanceMaximo.toFixed(2) + '%'
            });
            $(this).parent().html(currentValue);
            return;
        }

        if (newAvance !== currentAvance) {
            $(this).parent().html(newAvance.toFixed(2) + '%');

            // Actualizar TotalHoras si el campo editado es "Avance"
            var peso1 = parseFloat(row.find('[data-field="PESO"]').text());
            if (!isNaN(peso1)) {
                var totalHoras = (newAvance / 100) * peso1;
                row.find('.totHoras').text(totalHoras.toFixed(2));
                var idPrograma = row.find('td:first').text();
                updateTotalHoras(idPrograma, totalHoras.toFixed(2));
            }

            // Enviar la actualización al servidor
            updateRecord(row.find('td:first').text(), 'Avance', newAvance);
        } else {
            $(this).parent().html(currentValue);
        }
    });
}

function handleEditSemana(cell) {
    var row = $(cell).closest('tr');
    var currentSemana = $(cell).text().trim();
    var semanaDuplicada = false;

    // Verificar si la semana ya está registrada en otra fila
    $('.editable[data-field="Semana"]').each(function() {
        var semana = $(this).text().trim();
        if (semana === currentSemana && $(this).closest('tr').get(0) !== row.get(0)) {
            semanaDuplicada = true;
        }
    });

    if (semanaDuplicada) {
        Swal.fire({
            icon: 'warning',
            title: 'Semana ya registrada',
            text: 'No se puede modificar la semana a una que ya está registrada.'
        });
        return;
    }

    // Hacer editable el campo de "Semana"
    var currentValue = $(cell).text();
    $(cell).html('<input type="text" class="edit-input" value="' + currentValue + '">');
    var inputField = $(cell).find('input');
    inputField.focus();

    // Detectar cuando el usuario termine de editar
    inputField.on('blur', function() {
        var newValue = $(this).val().trim();

        if (!newValue) {
            Swal.fire({
                icon: 'warning',
                title: 'Valor inválido',
                text: 'Por favor ingresa un valor válido para la semana.'
            });
            $(this).parent().html(currentValue);
            return;
        }

        // Verificar nuevamente si la nueva semana está duplicada
        var nuevaSemanaDuplicada = false;
        $('.editable[data-field="Semana"]').each(function() {
            var semana = $(this).text().trim();
            if (semana === newValue && $(this).closest('tr').get(0) !== row.get(0)) {
                nuevaSemanaDuplicada = true;
            }
        });

        if (nuevaSemanaDuplicada) {
            Swal.fire({
                icon: 'warning',
                title: 'Semana ya registrada',
                text: 'No se puede modificar la semana a una que ya está registrada.'
            });
            $(this).parent().html(currentValue);
            return;
        }

        // Si el valor es válido y no está duplicado, actualizar la semana
        $(this).parent().html(newValue);

        // Enviar la actualización al servidor
        var idPrograma = row.find('td:first').text();
        updateRecord(idPrograma, 'Semana', newValue);
    });
}

function handleEditAniio(cell) {
    var row = $(cell).closest('tr');
    var currentAniio = $(cell).text().trim();
    
    // Hacer editable el campo de "Año"
    var currentValue = $(cell).text();
    $(cell).html('<input type="text" class="edit-input" value="' + currentValue + '">');
    var inputField = $(cell).find('input');
    inputField.focus();

    // Detectar cuando el usuario termine de editar
    inputField.on('blur', function() {
        var newValue = $(this).val().trim();

        if (!newValue) {
            Swal.fire({
                icon: 'warning',
                title: 'Valor inválido',
                text: 'Por favor ingresa un valor válido para el año.'
            });
            $(this).parent().html(currentValue);
            return;
        }

        // Si el valor es válido, actualizar el "Año"
        $(this).parent().html(newValue);

        // Enviar la actualización al servidor
        var idPrograma = row.find('td:first').text();
        updateRecord(idPrograma, 'Aniio', newValue);
    });
}

function updateTotalHoras(idPrograma, totalHoras) {
    $.ajax({
        url: 'consultasAjax1/consulta6.php',
        type: 'POST',
        data: {
            idPrograma: idPrograma,
            totalHoras: totalHoras
        },
        success: function(response) {
            console.log('TotalHoras actualizado correctamente en la base de datos');
        },
        error: function() {
            console.error('Error al actualizar TotalHoras');
        }
    });
}

function updateRecord(idPrograma, field, newValue) {
    if (field === 'AvanceReal') {
        console.log('updateRecord AvanceReal', { idPrograma, newValue });
    }
    // Cliente: validar que la suma total de Avance/AvanceReal no exceda 100%
    if (field === 'AvanceReal' || field === 'Avance') {
        var totalOther = 0;
        // Recorrer todas las celdas de ese campo y sumar, excluyendo el idPrograma actual
        $('.editable[data-field="' + field + '"]').each(function() {
            var $cell = $(this);
            var $row = $cell.closest('tr');
            var pid = $row.find('td[data-field="IdPrograma"]').text().trim();
            if (String(pid) === String(idPrograma)) return; // excluir actual

            var $inp = $cell.find('input.edit-input');
            var val = 0;
            if ($inp.length) val = parseFloat($inp.val()) || 0;
            else val = parseFloat($cell.text().replace('%', '').trim()) || 0;
            totalOther += val;
        });

        var newValNum = parseFloat(newValue) || 0;
        if (totalOther + newValNum > 100.0001) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite excedido',
                text: 'La suma total de "' + field + '" no puede superar 100%.'
            });
            return;
        }
    }
    $.ajax({
        url: 'consultasAjax1/consulta5.php',
        type: 'POST',
        data: {
            idPrograma: idPrograma,
            field: field,
            newValue: newValue
        },
        success: function(response) {
            let data = response;
            try {
                if (typeof response === 'string') {
                    data = JSON.parse(response);
                }
            } catch (e) {
                console.error('Error parseando respuesta de updateRecord', { response, error: e });
            }

            if (field === 'AvanceReal') {
                console.log('Respuesta updateRecord AvanceReal', { data });
            }

            if (data && data.success) {
                if (field === 'AvanceReal') {
                    console.log('Registro AvanceReal actualizado correctamente');
                }
                if (field === 'AvanceReal' && typeof onAvanceRealUpdated === 'function') {
                    onAvanceRealUpdated(idPrograma);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: 'Error al actualizar el registro'
                });
            }
        },
        error: function() {
            console.error('Error en la actualización del registro');
        }
    });
}

// Función auxiliar para obtener todos los procesos tipo MO** de la tabla de subconsulta
function obtenerProcesosMO() {
    const fallback = [
        'MO001_1','MO001_2','MO002','MO004','MO014','MO008_1','MO008_2','MO007_1','MO007_2',
        'MO003','MO015','MO005','MO009','MO004P','MO006','MO012','MO011','MO020','MO010',
        'MO050','MO060','MO070','SUT'
    ];
    const cols = (window.subQueryTable && window.subQueryTable.settings)
        ? (window.subQueryTable.settings()[0].aoColumns || [])
        : [];
    const dinamicos = cols
        .map(col => col.mData || col.data)
        .filter(name => typeof name === 'string' && name.startsWith('MO'));
    const lista = dinamicos.length ? dinamicos : fallback;
    return lista;
}

// Busca la fila en la tabla subQueryTable por IdPrograma
function obtenerFilaPorIdPrograma(idPrograma) {
    if (!window.subQueryTable || !window.subQueryTable.rows) return null;
    const data = window.subQueryTable.rows().data();
    for (let i = 0; i < data.length; i++) {
        const row = data[i];
        const valorId = row.IdPrograma || row.idPrograma || row.idprograma;
        if (valorId != null && String(valorId) === String(idPrograma)) {
            return row;
        }
    }
    return null;
}

// Callback al actualizar cualquier Avance Real
function onAvanceRealUpdated(idPrograma) {
    const procesosMO = obtenerProcesosMO();
    const payload = {
        idPadreOT: window.idPadreOT || window.IdPadreOT || null,
        codPdre: window.codPdre || window.CodPdre || null,
        projectCode: window.projectCode || window.ProjectCode || null
    };

    if (!payload.idPadreOT || !payload.codPdre) {
        console.warn('Faltan parámetros para obtener valores MO', payload);
        return;
    }

    $.ajax({
        url: 'consultasAjax1/consulta_procesos_mo.php',
        type: 'POST',
        data: payload,
        dataType: 'json',
        success: function(resp) {
            if (!resp || !resp.success || !resp.data) {
                console.warn('No se pudieron obtener valores MO', resp);
                return;
            }

            const fila = resp.data;
            const valoresMO = procesosMO.map(proceso => ({
                proceso,
                valor: fila[proceso]
            }));
              console.log('AvanceReal actualizado', {
                idPrograma,
                idPadreOT: payload.idPadreOT,
                codPdre: payload.codPdre,
                projectCode: payload.projectCode,
                procesosMO: valoresMO
            });

            // Recalcular y persistir el avance real ponderado del proyecto
            recalcularAvanceRealProyecto(payload);
        },
        error: function(xhr, status, error) {
            console.error('Error consultando valores MO', { status, error, response: xhr.responseText });
        }
    });
}

// Calcula en backend el avance real ponderado y lo guarda en othija (si columna existe)
function recalcularAvanceRealProyecto(payload) {
    $.ajax({
        url: 'consultasAjax1/calcular_avance_real_proyecto.php',
        type: 'POST',
        data: {
            idPadreOT: payload.idPadreOT,
            codPdre: payload.codPdre,
            projectCode: payload.projectCode
        },
        dataType: 'json',
        success: function(resp) {
            if (!resp || !resp.success) {
                console.warn('No se pudo recalcular avance real de proyecto', resp);
                return;
            }
            console.log('AvanceRealProyecto (ponderado por horas)', {
                porcentaje: resp.avanceRealProyecto,
                maxPercent: resp.maxPercent,
                totalHorasProyecto: resp.totalHorasProyecto,
                horasAvanzadas: resp.horasAvanzadas,
                detalle: resp.detalle,
                sapUpdate: resp.sapUpdate,
                projectCode: resp.projectCode,
                codPdre: resp.codPdre
            });
        },
        error: function(xhr, status, error) {
            console.error('Error recalculando avance real de proyecto', { status, error, response: xhr.responseText });
        }
    });
}

function EliminarRow(button) {
    var rowToDelete = $(button).closest('tr');  
    var estado = rowToDelete.find('[data-field="Estado"]').text().trim();
    var IdPrograma = rowToDelete.find('[data-field="IdPrograma"]').text().trim();  
    
    console.log('=== ELIMINAR ROW ===');
    console.log('Estado:', estado);
    console.log('IdPrograma:', IdPrograma);
    
    // Verificar si la semana está cerrada
    if (estado === '2') {
        Swal.fire({
            icon: 'warning',
            title: 'Semana cerrada',
            text: 'No se puede eliminar porque la semana está cerrada'
        });
        return;
    }
    
    // Solo permitir eliminar si la semana está abierta (estado = 1) o sin estado
    if (estado !== '1' && estado !== '' && estado !== undefined) {
        Swal.fire({
            icon: 'warning',
            title: 'Estado inválido',
            text: 'No se puede eliminar en el estado actual'
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar eliminación?',
        text: '¿Seguro que deseas eliminar este registro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            if (IdPrograma && IdPrograma !== '' && IdPrograma !== 'SIN_ID') {
                console.log('Eliminando de BD con IdPrograma:', IdPrograma);
                // Eliminar de la base de datos
                eliminarRegistro(IdPrograma, function(success, message) {
                    console.log('Resultado eliminación:', success, message);
                    if (success) {
                        rowToDelete.remove();
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'El registro ha sido eliminado correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Recargar datos del div flotante
                        if (typeof window.idPadreOT !== 'undefined' && typeof ListarDatos === 'function') {
                            ListarDatos(window.idPadreOT, window.codPdre, window.columnName, function(response) {
                                ListarData(response);
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al eliminar',
                            text: message || 'No se pudo eliminar el registro de la base de datos'
                        });
                    }
                });
            } else {
                console.log('Sin IdPrograma válido, solo eliminando del DOM');
                // Solo eliminar visualmente (para filas nuevas sin ID)
                rowToDelete.remove();
                Swal.fire({
                    icon: 'success',
                    title: 'Fila eliminada',
                    text: 'La fila fue eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
}

function eliminarRegistro(IdPrograma, callback) {
    console.log('=== ELIMINAR REGISTRO ===');
    console.log('Enviando IdPrograma:', IdPrograma);
    
    $.ajax({
        url: 'consultasAjax1/consulta4.php',
        type: 'POST',
        data: { IdPrograma: IdPrograma },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            if (response && response.success === true) {
                callback(true, response.message || 'Eliminado correctamente');
            } else {
                console.log('Error en respuesta:', response);
                callback(false, response.message || 'Error al eliminar el registro');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            console.error('Status:', status);
            console.error('ResponseText:', xhr.responseText);
            let message = 'Error de conexión con el servidor';
            try {
                if (xhr && xhr.responseText) {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) message = parsed.message;
                }
            } catch (e) { /* ignore parse errors */ }
            callback(false, message);
        }
    });
}

// Hacer las funciones disponibles globalmente
window.initializeDivFlotanteEditableEvents = initializeDivFlotanteEditableEvents;
window.handleEditAvance = handleEditAvance;
window.handleEditSemana = handleEditSemana;
window.handleEditAniio = handleEditAniio;
window.updateTotalHoras = updateTotalHoras;
window.updateRecord = updateRecord;
window.EliminarRow = EliminarRow;
window.eliminarRegistro = eliminarRegistro;

// Función para inicializar el evento del botón Guardar Datos
function initializeGuardarDatosButton() {
    console.log('Inicializando evento del botón Guardar Datos');
    $('#guardarDatos').off('click').on('click', function() {
        // Verificar que las variables globales necesarias estén disponibles
        if (!window.idPadreOT || !window.projectCode || !window.columnName || !window.codPdre) {
            console.error('Variables globales faltantes:', {
                idPadreOT: window.idPadreOT,
                projectCode: window.projectCode,
                columnName: window.columnName,
                codPdre: window.codPdre
            });
            Swal.fire({
                icon: 'error',
                title: 'Error de contexto',
                text: 'Faltan datos necesarios para guardar. Intenta reabrir esta ventana.'
            });
            return;
        }
        
        const filas = $('#div-flotante .tabla-dinamica tbody tr');
        console.log('Número de filas:', filas.length);
        let datosTabla = [];
        var sumatotal = 0;
        
        filas.each(function(index) {
            var $row = $(this);
            
            // Ignorar filas de placeholder que dicen "No se encontraron datos." o filas con una sola celda colspan
            var $tds = $row.find('td');
            if ($tds.length === 1) {
                var tdText = $tds.text().trim();
                var colspan = $tds.attr('colspan');
                if ((colspan && parseInt(colspan) > 1) || tdText.toLowerCase().includes('no se encontraron')) {
                    console.log('Saltando fila placeholder', index, tdText);
                    return; // continuar al siguiente tr
                }
            }
            
            var anio = '';
            var semana = '';
            var porcentaje1 = 0;
            var totalHoras = '';
            
            // Caso 1: fila nueva creada por insertRowInFlotanteTable (selects + inputs)
            if ($row.find('select.Aniio, #Aniio').length > 0 || $row.find('select.Semana, #Semana').length > 0) {
                anio = ($row.find('select.Aniio, #Aniio').val() || '').toString().trim();
                semana = ($row.find('select.Semana, #Semana').val() || '').toString().trim();
                // Fallback: si el value está vacío, intentar obtener el texto de la opción seleccionada
                if (!semana) {
                    var optText = $row.find('select.Semana option:selected, #Semana option:selected').text() || '';
                    semana = optText.toString().trim();
                }
                var $av = $row.find('input.Avance');
                porcentaje1 = $av.length > 0 ? (parseFloat($av.val()) || 0) : 0;
                var $th = $row.find('input.TotalHoras, .TotalHoras');
                totalHoras = $th.length > 0 ? $th.val() : ($row.find('.TotalHoras').val() || '');
            } else if ($row.find('td[data-field="Aniio"]').length > 0) {
                // Caso 2: fila existente renderizada por ListarData (td[data-field])
                anio = $row.find('td[data-field="Aniio"]').text().trim() || '';
                semana = $row.find('td[data-field="Semana"]').text().trim() || '';
                // Avance puede ser input (editable) o texto con '%'
                var $avInput = $row.find('input');
                if ($avInput.length > 0) {
                    porcentaje1 = parseFloat($avInput.val()) || 0;
                } else {
                    var txtAv = $row.find('td[data-field="Avance"]').text() || '';
                    txtAv = txtAv.replace(/[^0-9\.\-]/g, '');
                    porcentaje1 = parseFloat(txtAv) || 0;
                }
                totalHoras = $row.find('td[data-field="TotHoras"]').text().trim() || ($row.find('.TotalHoras').val() || '');
            } else {
                // Fallback por índices (compatibilidad)
                var celdas = $row.find('td');
                anio = $(celdas[0]).find('select').val() || $(celdas[0]).text().trim() || '';
                semana = $(celdas[1]).find('select').val() || $(celdas[1]).text().trim() || '';
                var $avF = $(celdas[2]).find('input');
                if ($avF.length > 0) porcentaje1 = parseFloat($avF.val()) || 0;
                else porcentaje1 = parseFloat($(celdas[2]).text().replace(/[^0-9\.\-]/g, '')) || 0;
                var $thF = $(celdas[3]).find('input');
                totalHoras = $thF.length > 0 ? $thF.val() : $(celdas[3]).text().trim();
            }
            
            if (anio && semana && (porcentaje1 > 0 || totalHoras)) {
                // Capturar valor de fabricacion
                var fabricacion = 'tama'; // valor por defecto
                var $selectFabricacion = $row.find('select.select-fabricacion, select[data-field="fabricacion"]');
                if ($selectFabricacion.length > 0) {
                    fabricacion = $selectFabricacion.val() || 'tama';
                }
                
                console.log('Fila procesada ->', {
                    idPadreOT: window.idPadreOT,
                    proyecto: window.projectCode,
                    proceso: window.columnName,
                    codPdre: window.codPdre,
                    anio, 
                    semana, 
                    porcentaje: porcentaje1, 
                    totalHoras,
                    fabricacion
                });
                datosTabla.push({
                    idPadreOT: window.idPadreOT,       // Campo requerido por el servidor
                    proyecto: window.projectCode,      // Campo requerido por el servidor
                    proceso: window.columnName,        // Campo requerido por el servidor
                    codPdre: window.codPdre,          // Campo requerido por el servidor
                    anio: anio,
                    semana: semana, 
                    porcentaje: porcentaje1,
                    totalHoras: totalHoras,
                    fabricacion: fabricacion
                });
                sumatotal += porcentaje1;
            }
        });
        
        // Llamar a la función processTableData que ya existe
        if (typeof processTableData === 'function') {
            processTableData(datosTabla, sumatotal);
        } else {
            console.error('Función processTableData no encontrada');
        }
    });
}

// Hacer la función disponible globalmente
window.initializeGuardarDatosButton = initializeGuardarDatosButton;

// === FUNCIONALIDAD PARA REPORTE DE PROCESOS NO CERRADOS ===
function initializeReporteProcesosNoCerrados() {
    const reporteBtn = document.getElementById('reporte-procesos-no-cerrados');

    if (!reporteBtn) return;

    // Evitar abrir ventanas externas: si existe la función del modal, úsala y cancela el comportamiento por defecto
    if (typeof window.abrirModalReporte === 'function') {
        reporteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            try {
                window.abrirModalReporte(e);
            } catch (err) {
                console.error('Error al abrir el modal del reporte:', err);
            }
            return false;
        });
        return;
    }

    // Fallback sin modal: abrir en la misma pestaña (no ventana nueva)
    reporteBtn.addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = 'reporte_procesos_no_cerrados.php';
        return false;
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    initializeReporteProcesosNoCerrados();
});

// Hacer la función disponible globalmente
window.initializeReporteProcesosNoCerrados = initializeReporteProcesosNoCerrados;