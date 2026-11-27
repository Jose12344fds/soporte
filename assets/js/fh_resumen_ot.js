(function(g){
'use strict';
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
function date(s){var p=s.split('-').map(Number);return new Date(p[0],p[1]-1,p[2],12);}
function iso(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
function fmt(s){if(!s)return '—';return s.split('-').reverse().join('/');}
function num(n){return Number(Number(n||0).toFixed(3));}
function data(d){var rows=[],cursor=0,start=d.fecha_inicio,firstDays=0,secondDays=0;
 (d.mod2&&d.mod2.items||[]).forEach(function(p){var dias=Number(p.dias)||0;if(!start||dias<=0)return;var s=FHCalendar.slot(start,cursor,dias);cursor=s.cursor;firstDays+=dias;rows.push(Object.assign({modulo:1,codigo:p.cod_plano||'',proceso:p.actividad_nombre||p.desc_plano||'Ingeniería',dias:dias},s));});
 var next=d.mod3&&d.mod3.fecha_inicio;if(!next&&rows.length){var end=date(rows[rows.length-1].fecha_fin);end.setDate(end.getDate()+1);next=FHCalendar.workDate(iso(end),0);}cursor=0;
 (d.mod3&&d.mod3.items||[]).forEach(function(p){var dias=(Number(p.dias)||0)/8;if(!next||dias<=0)return;var s=FHCalendar.slot(next,cursor,dias);cursor=s.cursor;secondDays+=dias;rows.push(Object.assign({modulo:2,codigo:p.cod_plano||'',proceso:p.desc_mod3||'Detalle',dias:dias},s));});
 return {rows:rows,primero:firstDays,segundo:secondDays,total:firstDays+secondDays,inicio:rows.length?rows[0].fecha_inicio:null,fin:rows.length?rows[rows.length-1].fecha_fin:null};
}
function render(d){if(!d||!d.success){document.getElementById('rptEstadoPill').textContent='Sin datos';return;}
 var v=data(d),state=Number(d.estado),labels={'-1':'Registrada',0:'Módulo 1',1:'Módulo 1',2:'Módulo 2',4:'Pendiente de cierre',3:'Proyecto cerrado'};
 document.getElementById('rptOT').textContent=d.ot;document.getElementById('rptProy').textContent=d.proyecto||'';document.getElementById('rptEstadoPill').textContent=labels[state]||'Registrada';
 document.getElementById('rptPipeline').innerHTML='<div class="rpt-summary-footer">Registro de OT → Módulo 1 → Módulo 2 → <strong>'+esc(labels[state]||'Registro')+'</strong></div>';
 var cards=[['Módulo 1 · Ingeniería',num(v.primero)+' días',num(v.primero*8)+' horas'],['Módulo 2 · Detalle',num(v.segundo)+' días',num(v.segundo*8)+' horas'],['Total planificado',num(v.total)+' días',num(v.total*8)+' horas'],['Fin previsto',fmt(v.fin),'Jornadas L–V de 8 horas']];
 var html='<div style="width:100%;display:flex;flex-wrap:wrap;gap:12px;">';cards.forEach(function(c){html+='<div class="rpt-kpi-card" style="flex:1;min-width:170px;"><div class="rpt-kpi-tag">'+esc(c[0])+'</div><div style="font-size:1.3rem;font-weight:800;color:#17365a;">'+esc(c[1])+'</div><div class="rpt-kpi-unit">'+esc(c[2])+'</div></div>';});html+='</div>';
 html+='<div style="width:100%;overflow:auto;"><table class="fp-table"><thead><tr><th>Módulo</th><th>Plano / proceso</th><th>Días</th><th>Horas</th><th>Inicio</th><th>Fin</th></tr></thead><tbody>';
 v.rows.forEach(function(r){html+='<tr><td>'+r.modulo+'</td><td><strong>'+esc(r.codigo)+'</strong> · '+esc(r.proceso)+'</td><td>'+num(r.dias)+'</td><td>'+num(r.horas)+'</td><td>'+fmt(r.fecha_inicio)+'</td><td>'+fmt(r.fecha_fin)+'</td></tr>';});html+='</tbody></table></div>';document.getElementById('rptKpiRow').innerHTML=html;
 document.getElementById('rptSummaryFooter').innerHTML='<span>Inicio: <strong>'+fmt(v.inicio)+'</strong></span><span>Sin feriados · Horas planificadas</span><a href="../../api/fh_ing/exportar_gantt.php?ot='+encodeURIComponent(d.ot)+'">Descargar reporte completo</a>'+(state===3?'<a href="proyecto_finalizado.php?ot='+encodeURIComponent(d.ot)+'">Expediente y documentos</a>':'');
 var chart=document.getElementById('rptGanttBody');if(!v.rows.length){chart.textContent='Aún no hay duraciones planificadas.';return;}
 var days=[],a=date(v.inicio),b=date(v.fin);while(a<=b){days.push(iso(a));a.setDate(a.getDate()+1);}
 var gantt='<div style="overflow:auto;"><table class="fp-table"><thead><tr><th>Módulo / plano</th><th>Duración</th>';days.forEach(function(day){gantt+='<th style="min-width:45px;">'+day.substring(8)+'/'+day.substring(5,7)+'</th>';});gantt+='</tr></thead><tbody>';
 v.rows.forEach(function(r){var color=r.modulo===1?'#dbeafe':'#dcfce7';gantt+='<tr><td>M'+r.modulo+' · '+esc(r.codigo)+'</td><td>'+num(r.dias)+' d</td>';days.forEach(function(day){var s=r.segmentos.find(function(x){return x.fecha===day;});var weekend=[0,6].indexOf(date(day).getDay())>=0;gantt+='<td style="text-align:center;background:'+(s?color:weekend?'#e2e8f0':'white')+';">'+(s?num(s.horas)+' h':'')+'</td>';});gantt+='</tr>';});gantt+='</tbody></table></div>';chart.innerHTML=gantt;
}
g.FHResumenOT={data:data,render:render};
})(window);