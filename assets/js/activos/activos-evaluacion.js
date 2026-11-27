function iniciarEvaluacionActivos(tablaActivos){
    function actualizarResultadoEvaluacion() {
        var valores=[];
        $('#modalEvaluarActivo .evaluacion-factor').each(function(){ valores.push(parseInt(this.dataset.value || 0,10)); });
        var maximo=Math.max.apply(null,valores);
        var nivel=({1:'BAJO',2:'MEDIO',3:'ALTO',4:'MUY ALTO'})[maximo] || 'SIN EVALUAR';
        $('#evaluarNivel').text(nivel);
        $('#evaluarPuntaje').text('Puntaje máximo: ' + (maximo || 0));
        return {valores:valores,maximo:maximo,nivel:nivel};
    }
    $(document).on('click','.btn-evaluar-activo',function(){
        $('#evaluarActivoId').val($(this).data('id'));
        $('#evaluarActivoHost').text($(this).data('host') || '-');
        var valores={Confidencialidad:$(this).data('c'),Disponibilidad:$(this).data('d'),Integridad:$(this).data('i')};
        $('#modalEvaluarActivo .evaluacion-factor').each(function(){
            var valor=parseInt(valores[this.dataset.factor] || 0,10); this.dataset.value=valor || '';
            $(this).find('.evaluacion-score').removeClass('active').filter('[data-value="'+valor+'"]').addClass('active');
        });
        var clasificacion=String($(this).data('clasificacion') || '');
        $('#evaluarClasificacion label').removeClass('active');
        $('#evaluarClasificacion input').prop('checked',false).filter('[value="'+clasificacion+'"]').prop('checked',true).closest('label').addClass('active');
        actualizarResultadoEvaluacion(); $('#modalEvaluarActivo').modal('show');
    });
    $('#modalEvaluarActivo').on('click','.evaluacion-score',function(){
        var factor=$(this).closest('.evaluacion-factor'); factor.attr('data-value',this.dataset.value);
        factor.find('.evaluacion-score').removeClass('active'); $(this).addClass('active'); actualizarResultadoEvaluacion();
    });
    $('#btnGuardarEvaluacion').on('click',function(){
        var factors={};
        $('#modalEvaluarActivo .evaluacion-factor').each(function(){ factors[this.dataset.factor]=parseInt(this.dataset.value||0,10); });
        var clasificacion=$('#evaluarClasificacion input:checked').val() || '';
        if (!factors.Confidencialidad || !factors.Disponibilidad || !factors.Integridad || !clasificacion) {
            Swal.fire('Evaluación incompleta','Seleccione los tres puntajes y la clasificación.','warning'); return;
        }
        var button=$(this), original=button.html(); button.prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando');
        ActivosAPI.evaluar( {id:$('#evaluarActivoId').val(),confidencialidad:factors.Confidencialidad,disponibilidad:factors.Disponibilidad,integridad:factors.Integridad,clasificacion:clasificacion})
          .then(function(j){ $('#modalEvaluarActivo').modal('hide');tablaActivos.ajax.reload(null,false);Swal.fire({icon:'success',title:'Evaluación guardada',text:'Criticidad: '+j.criticidad,timer:1600,showConfirmButton:false});})
          .catch(function(e){Swal.fire('No se pudo guardar',e.message,'error');})
          .finally(function(){button.prop('disabled',false).html(original);});
    });


}
