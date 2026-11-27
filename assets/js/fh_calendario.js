/* Calendario compartido de jornadas de 8 h, lunes a viernes. */
(function(g){
'use strict';
function parse(s){var a=s.split('-').map(Number);return new Date(a[0],a[1]-1,a[2],12);}
function iso(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
function workDate(start,index){var d=parse(start);while(d.getDay()===0||d.getDay()===6)d.setDate(d.getDate()+1);d.setDate(d.getDate()+Math.floor(index/5)*7);for(var i=0;i<index%5;i++){do{d.setDate(d.getDate()+1);}while(d.getDay()===0||d.getDay()===6);}return iso(d);}
function slot(start,cursor,days){var seconds=Math.round(Number(days)*28800);if(!Number.isFinite(seconds)||seconds<0||seconds>28800*10000)throw Error('Duración fuera de rango');if(!seconds)return {cursor:cursor,fecha_inicio:null,fecha_fin:null,horas:0,inicio_jornada:0,fin_jornada:0,segmentos:[]};var end=cursor+seconds,first=Math.floor(cursor/28800),last=Math.floor((end-1)/28800),parts=[];for(var i=first;i<=last;i++){var a=Math.max(cursor,i*28800)-i*28800,b=Math.min(end,(i+1)*28800)-i*28800;parts.push({fecha:workDate(start,i),inicio:a/28800,fraccion:(b-a)/28800,horas:(b-a)/3600});}return {cursor:end,fecha_inicio:workDate(start,first),fecha_fin:workDate(start,last),horas:seconds/3600,inicio_jornada:(cursor%28800)/3600,fin_jornada:(end-last*28800)/3600,segmentos:parts};}
function activities(acts,start){var cursor=0;return acts.map(function(act){var a=Object.assign({},act,{total_dias:0,total_horas:0,fecha_inicio:null,fecha_fin:null});a.planos=(act.planos||[]).map(function(p){var s=slot(start,cursor,p.dias||0);cursor=s.cursor;var row=Object.assign({},p,s);a.total_dias+=Number(p.dias)||0;a.total_horas+=s.horas;if(s.fecha_inicio){if(!a.fecha_inicio)a.fecha_inicio=s.fecha_inicio;a.fecha_fin=s.fecha_fin;}return row;});return a;});}
g.FHCalendar={slot:slot,activities:activities,workDate:workDate};
})(typeof window==='undefined'?globalThis:window);
