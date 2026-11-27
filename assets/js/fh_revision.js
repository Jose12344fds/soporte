(function(g){
'use strict';
var root=null,previousFocus=null,items=[],index=0,scale=1,rotation=0,canvas=null,ctx=null,base=null,ops=[],active=null,options=null,mode='arrow';
function el(tag,attrs,text){var e=document.createElement(tag);Object.keys(attrs||{}).forEach(function(k){e.setAttribute(k,attrs[k]);});if(text)e.textContent=text;return e;}
function button(text,fn,label){var b=el('button',{type:'button',title:label||text,'aria-label':label||text},text);b.addEventListener('click',fn);return b;}
function setup(title){
 if(root)close();previousFocus=document.activeElement;
 root=el('div',{'class':'fh-review-backdrop'});var dialog=el('section',{'class':'fh-review-dialog',role:'dialog','aria-modal':'true','aria-labelledby':'fhReviewTitle'});
 var header=el('header',{'class':'fh-review-header'});header.appendChild(el('strong',{id:'fhReviewTitle'},title||'Imagen de la anotación'));header.appendChild(button('×',close,'Cerrar imagen'));
 root.appendChild(dialog);dialog.appendChild(header);dialog.appendChild(el('div',{'class':'fh-review-tools'}));dialog.appendChild(el('div',{'class':'fh-review-stage'}));dialog.appendChild(el('div',{'class':'fh-review-status',role:'status','aria-live':'polite'}));
 root.addEventListener('click',function(e){if(e.target===root)close();});document.body.appendChild(root);header.querySelector('button').focus();return dialog;
}
function status(text){if(root)root.querySelector('.fh-review-status').textContent=text;}
function close(){if(!root)return;root.remove();root=null;canvas=ctx=base=null;ops=[];active=null;options=null;if(previousFocus&&previousFocus.isConnected)previousFocus.focus();}
function transform(){if(!root)return;var im=root.querySelector('.fh-review-photo');if(im&&im.naturalWidth){var stage=root.querySelector('.fh-review-stage'),frame=im.parentNode;var fit=Math.min((stage.clientWidth-32)/im.naturalWidth,(stage.clientHeight-32)/im.naturalHeight,1);var w=im.naturalWidth*fit*scale,h=im.naturalHeight*fit*scale;im.style.width=w+'px';im.style.height=h+'px';im.style.transform='translate(-50%,-50%) rotate('+rotation+'deg)';frame.style.width=(rotation%180?h:w)+'px';frame.style.height=(rotation%180?w:h)+'px';}status((index+1)+' / '+items.length+' · '+Math.round(scale*100)+' %');}
function showImage(){var stage=root.querySelector('.fh-review-stage');stage.innerHTML='';var im=el('img',{'class':'fh-review-photo',alt:items[index].title||'Imagen adjunta'});im.onload=transform;im.onerror=function(){status('No se pudo cargar la imagen. Comprueba tu sesión e inténtalo de nuevo.');};im.src=items[index].url;stage.style.display='block';var frame=el('div',{'class':'fh-review-photo-frame'});frame.appendChild(im);stage.appendChild(frame);root.querySelector('#fhReviewTitle').textContent=items[index].title||'Imagen adjunta';scale=1;rotation=0;transform();}
function openGallery(list,start){if(!list||!list.length)return;items=list;index=Math.max(0,Math.min(Number(start)||0,list.length-1));var d=setup('Imagen adjunta'),tools=d.querySelector('.fh-review-tools');
 tools.appendChild(button('←',function(){index=(index-1+items.length)%items.length;showImage();},'Imagen anterior'));
 tools.appendChild(button('→',function(){index=(index+1)%items.length;showImage();},'Imagen siguiente'));
 tools.appendChild(button('−',function(){scale=Math.max(.25,scale-.25);transform();},'Reducir'));
 tools.appendChild(button('+',function(){scale=Math.min(4,scale+.25);transform();},'Ampliar'));
 tools.appendChild(button('Ajustar',function(){scale=1;rotation=0;transform();}));
 tools.appendChild(button('Girar',function(){rotation=(rotation+90)%360;transform();}));showImage();
}
function draw(op){ctx.strokeStyle=op.color;ctx.fillStyle=op.color;ctx.lineWidth=Math.max(3,canvas.width/400);ctx.lineCap='round';ctx.lineJoin='round';
 var a=op.points[0],b=op.points[op.points.length-1];
 if(op.type==='text'){ctx.font='bold '+Math.max(18,canvas.width/65)+'px Arial';ctx.fillText(op.text,a.x,a.y);return;}
 if(op.type==='box'){ctx.strokeRect(a.x,a.y,b.x-a.x,b.y-a.y);return;}
 ctx.beginPath();ctx.moveTo(a.x,a.y);op.points.slice(1).forEach(function(p){ctx.lineTo(p.x,p.y);});ctx.stroke();
 if(op.type==='arrow'){var angle=Math.atan2(b.y-a.y,b.x-a.x),size=Math.max(16,canvas.width/65);ctx.beginPath();ctx.moveTo(b.x,b.y);ctx.lineTo(b.x-size*Math.cos(angle-.5),b.y-size*Math.sin(angle-.5));ctx.moveTo(b.x,b.y);ctx.lineTo(b.x-size*Math.cos(angle+.5),b.y-size*Math.sin(angle+.5));ctx.stroke();}
}
function paint(){if(!ctx||!base)return;ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(base,0,0,canvas.width,canvas.height);ops.forEach(draw);if(active)draw(active);}
function point(e){var r=canvas.getBoundingClientRect();return {x:(e.clientX-r.left)*canvas.width/r.width,y:(e.clientY-r.top)*canvas.height/r.height};}
function openMarkup(url,opts){var d=setup('Marcar captura · '+(opts.title||'Plano')),tools=d.querySelector('.fh-review-tools');options=opts;ops=[];active=null;mode='arrow';
 var select=el('select',{'aria-label':'Herramienta de marcado'});[['arrow','Flecha'],['box','Recuadro'],['pen','Trazo libre'],['text','Texto']].forEach(function(v){select.appendChild(el('option',{value:v[0]},v[1]));});select.addEventListener('change',function(){mode=select.value;});tools.appendChild(select);
 var color=el('input',{type:'color',value:'#ef4444','aria-label':'Color de las marcas'});tools.appendChild(color);
 var text=el('input',{type:'text',placeholder:'Texto para colocar en la captura',maxlength:'160','aria-label':'Texto de la marca'});tools.appendChild(text);
 tools.appendChild(button('Deshacer',function(){ops.pop();paint();}));tools.appendChild(button('Limpiar marcas',function(){ops=[];paint();}));
 var attach=button('Adjuntar a la anotación',function(){if(!base)return;attach.disabled=true;var callback=options.onAttach,session=root;canvas.toBlob(function(blob){if(root!==session)return;if(!blob){attach.disabled=false;status('No se pudo generar la captura.');return;}var file=new File([blob],'revision-plano-'+Date.now()+'.png',{type:'image/png'});close();callback(file);},'image/png');});attach.disabled=true;attach.classList.add('fh-review-primary');tools.appendChild(attach);
 canvas=el('canvas',{'class':'fh-review-canvas','aria-label':'Captura del plano. Arrastra para marcar; selecciona Texto y pulsa para escribir.'});ctx=canvas.getContext('2d');d.querySelector('.fh-review-stage').appendChild(canvas);status('Cargando captura…');
 var image=new Image(),markupSession=root;image.onload=function(){if(root!==markupSession||!canvas)return;base=image;canvas.width=image.naturalWidth;canvas.height=image.naturalHeight;paint();attach.disabled=false;status('Marca la captura y adjúntala. Después guarda la anotación para conservarla.');if(opts.revokeURL&&url.indexOf('blob:')===0)URL.revokeObjectURL(url);};image.onerror=function(){status('No se pudo cargar la captura.');};image.src=url;
 canvas.addEventListener('pointerdown',function(e){if(!base)return;e.preventDefault();canvas.setPointerCapture(e.pointerId);var p=point(e);active={type:mode,color:color.value,points:[p,p],text:text.value.trim()};if(mode==='text'){if(!active.text){status('Escribe el texto en la barra antes de colocarlo.');active=null;return;}ops.push(active);active=null;paint();}});
 canvas.addEventListener('pointermove',function(e){if(!active)return;var p=point(e);if(mode==='pen')active.points.push(p);else active.points[1]=p;paint();});
 function finish(){if(active){ops.push(active);active=null;paint();}}
 canvas.addEventListener('pointerup',finish);canvas.addEventListener('pointercancel',function(){active=null;paint();});
}
document.addEventListener('keydown',function(e){if(!root)return;if(e.key==='Escape'){e.preventDefault();e.stopImmediatePropagation();close();return;}if(e.key==='Tab'){var focus=root.querySelectorAll('button:not(:disabled),select,input');var first=focus[0],last=focus[focus.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}if(!canvas&&e.key==='ArrowRight'){e.preventDefault();index=(index+1)%items.length;showImage();}if(!canvas&&e.key==='ArrowLeft'){e.preventDefault();index=(index-1+items.length)%items.length;showImage();}},true);
g.FHReview={openGallery:openGallery,openMarkup:openMarkup,close:close,isOpen:function(){return !!root;}};
})(window);